<?php

/**
 * Author: Pavel Petrov <itnelo@gmail.com>
 * Date: 11.02.16 16:33
 */

namespace yii\tools\crud;

use Yii;
use yii\helpers\VarDumper;
use yii\base\Model;
use yii\base\ActionEvent;
use yii\tools\components\Action as BaseAction;
use yii\tools\traits\AjaxValidationTrait;

/**
 * Class CrudAction
 *
 * Present basic skeleton for Create/Read/Update/Delete action
 * Note: CRUD actions available only for models with implemented ActiveRecordInterface
 *
 * @property string $type create|read|update|delete (read-only)
 * @package yii\tools\crud
 */
abstract class Action extends BaseAction
{
    use AjaxValidationTrait;

    const EVENT_BEFORE_CRUD_ACTION = 'beforeCrudAction';
    const EVENT_AFTER_CRUD_ACTION = 'afterCrudAction';

    const MODEL_LOAD_POLICY_NONE = 'none';
    const MODEL_LOAD_POLICY_REQUIRED = 'required';

    /**
     * Data location for load model stage
     * POST, GET (case insensitive)
     * if not defined - data location determines in runtime and depends on \yii\web\Request::$method
     * @var string
     */
    public $method;
    /**
     * Inherited from BaseAction, narrowed to models with ActiveRecordInterface implemented
     * @var \yii\db\ActiveRecordInterface|\yii\db\ActiveRecordInterface[]|string
     */
    public $model;
    /**
     * Method of the model which will be called after validation stage
     * If $model isn't instanceof ActiveRecordInterface, implementation of this method in class will be checked
     * @var string
     */
    public $modelAction;
    /**
     * Default action validation scenario
     * @var string
     */
    public $scenario = false;
    /**
     * performAjaxValidation requirment
     * @var bool
     */
    public $validation = false;
    /**
     * Redirect if crud action executing finished without errors
     * @var array|string
     */
    public $redirectSuccess = ['index'];
    /**
     * Redirect if errors occured during crud action executing
     * @var array|string
     */
    public $redirectError = ['index'];
    /**
     * Enable/Disable flash messages after execution of CRUD action
     * @var bool
     */
    public $flash = true;
    /**
     * Set session flash after successful action executing
     * @var string
     */
    public $flashSuccess;
    /**
     * Set session flash after failed action executing
     * @var bool
     */
    public $flashError;
    /**
     * Callback, will be invoked before Crud action starts
     *
     * ```
     * function (\yii\base\ActionEvent $event) {
     *     $action = $event->action;
     *     $type = $action->getType();      // create|read|update|delete
     *
     *     $model = $action->model;         // model|array of models (based on $action->multiple)
     *     $model->name = 'My model';
     *
     *     return true;                     // false (===) stops action execution
     * }
     * ```
     *
     * @var callable
     */
    public $beforeCrudAction;
    /**
     * Callback, will be invoked after Crud action ends (even if execution failed)
     *
     * ```
     * function (\yii\base\ActionEvent $event) {
     *     $action = $event->action;
     *     $type = $action->getType();     // create|read|update|delete
     *     $result = $action->isSuccess();  // bool (true|false) - result of create/update/delete
     *     $response = $event->result;
     *
     *     // After create action models have their Ids
     *     $models = $action->model;
     *     $action->redirectSuccess = ['show', 'id' => $models[0]->id];
     *
     *     // replace result of action
     *     $event->result = 'New response';
     *     $action->redirectSuccess = false;
     *     $action->redirectError = false;
     * }
     * ```
     *
     * @var callable
     */
    public $afterCrudAction;
    /**
     * @inheritdoc
     */
    public $modelPolicy = self::MODEL_POLICY_REQUIRED;
    /**
     * Policy declares behavior for loadModel() stage
     * If 'none', loading model via load() method of ActiveRecord (data from $_GET or $_POST) not performs
     * @var string
     */
    public $modelLoadPolicy = self::MODEL_LOAD_POLICY_REQUIRED;
    /**
     * @var \yii\db\Transaction
     */
    protected $transaction;
    /**
     * Result of CRUD action(s) executing
     * @var bool
     */
    protected $result;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // @todo Yii::t() yii2-crud
        if (!isset($this->flashSuccess)) {
            $this->flashSuccess = Yii::t('yii2-crud', 'Action successfully executed');
        }

        if (!isset($this->flashError)) {
            $this->flashError = Yii::t('yii2-crud', 'Action execution failed');
        }

        if (is_callable($this->beforeCrudAction)) {
            $this->on(static::EVENT_BEFORE_CRUD_ACTION, $this->beforeCrudAction);
        }

        if (is_callable($this->afterCrudAction)) {
            $this->on(static::EVENT_AFTER_CRUD_ACTION, $this->afterCrudAction);
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->result;
    }

    /**
     * @inheritdoc
     */
    final protected function runInternal()
    {
        if ($this->beforeCrudActionRun()) {
            if ($this->multiple && !empty($this->model)) {
                $this->transaction = $this->model[0]->getDb()->beginTransaction();
            }
            try {
                if (isset($this->modelAction)) {
                    Yii::info('Model method to be executed: ' . $this->modelAction, __METHOD__);
                    $this->runCrudAction($this->model);
                }
                if ($this->multiple && !empty($this->model)) {
                    $this->transaction->commit();
                }
                $this->result = true;
            } catch (\Exception $e) {
                if ($this->multiple && !empty($this->model)) {
                    $this->transaction->rollBack();
                }
                $this->result = false;
                Yii::error(VarDumper::dumpAsString($e), __METHOD__);
            }
            $this->afterCrudActionRun();
        }
    }

    /**
     * Load model stage
     * @return bool
     */
    protected function loadModel()
    {
        $this->ensureScenario();

        $request = Yii::$app->getRequest();
        $method = isset($this->method) ? strtoupper($this->method) : $request->method;
        $data = $method === 'POST' ? $request->post() : $request->get();
        $load = $this->multiple ? Model::loadMultiple($this->model, $data) : $this->model->load($data);

        if (!$load) {
            Yii::info("Model(s) isn't loaded (empty \$_GET or \$_POST data)", __METHOD__);

            return false;
        }

        Yii::info('Model(s) loaded'
            . ($this->scenario !== false ? ' with scenario: ' . $this->scenario : ''), __METHOD__);

        return true;
    }

    /**
     * Ensure what model has scenario before action execution
     */
    protected function ensureScenario()
    {
        if ($this->scenario === false) {
            return;
        }
        if ($this->multiple) {
            foreach ($this->model as $model) {
                $model->setScenario($this->scenario);
            }

            return;
        }
        $this->model->setScenario($this->scenario);
    }

    /**
     * Model validation stage
     * @return bool
     */
    protected function validateModel()
    {
        if (!Yii::$app->getRequest()->isAjax || Yii::$app->getRequest()->isPjax) {
            return;
        }
        $this->response = $this->performAjaxValidation($this->model);
    }

    /**
     * Setting CRUD result action flash
     * @return void
     */
    protected function setFlash()
    {
        $flash = $this->result ? ['success', $this->flashSuccess] : ['danger', $this->flashError];

        if (!is_array($flash[1])) {
            $flash[1] = [$flash[1]];
        }

        foreach ($flash[1] as $message) {
            Yii::$app->getSession()->addFlash($flash[0], $message);
        }

        Yii::info('Flash: ' . VarDumper::dumpAsString($flash), __METHOD__);
    }

    /**
     * Run Create/Read/Update/Delete action
     *
     * Set error flash and throw Exception, if action failed
     * Return nothing if action succeed
     *
     * @param \yii\db\ActiveRecord|\yii\db\ActiveRecord[] $model
     * @return void
     * @throws \Exception
     */
    protected function runCrudAction($model)
    {
        if ($model instanceof Model) {
            if (!call_user_func([$model, $this->modelAction])) {
                if ($model->hasErrors()) {
                    $this->flashError = [];
                    foreach ($model->getErrors() as $errors) {
                        $this->flashError = array_merge($this->flashError, $errors);
                    }
                }
                throw new \Exception($this->flashError ? VarDumper::dumpAsString($this->flashError) : 'Unknown error');
            }

            return;
        }

        foreach ($model as $concreteModel) {
            $this->runCrudAction($concreteModel);
        }
    }

    /**
     * @return bool
     */
    protected function beforeCrudActionRun()
    {
        if ($this->modelPolicy == self::MODEL_POLICY_REQUIRED) {
            Yii::trace('Model(s) loading', __METHOD__);
            if (!$this->loadModel() && $this->modelLoadPolicy != self::MODEL_LOAD_POLICY_NONE) {
                return false;
            }
        }

        if ($this->validation) {
            Yii::trace('Model(s) validation', __METHOD__);
            $this->validateModel();
            if ($this->response) {
                return false;
            }
        }

        $event = new ActionEvent($this);
        $this->trigger(static::EVENT_BEFORE_CRUD_ACTION, $event);

        return $event->isValid;
    }

    /**
     * @return callable|\yii\web\Response
     */
    protected function afterCrudActionRun()
    {
        $event = new ActionEvent($this);
        $event->result = $this->response;
        $this->trigger(static::EVENT_AFTER_CRUD_ACTION, $event);

        if ($this->flash) {
            $this->setFlash();
        }

        if ($redirect = $this->buildRedirect()) {
            Yii::info('After crud action redirect: ' . VarDumper::dumpAsString($redirect), __METHOD__);
            $this->response = $this->controller->redirect($redirect);

            return;
        }

        $this->response = $event->result;
    }

    /**
     * @return array|null|string
     */
    protected function buildRedirect()
    {
        if ($this->result) {
            return ($redirect = $this->redirectSuccess()) !== false ? $redirect : null;
        }

        return ($redirect = $this->redirectError()) !== false ? $redirect : null;
    }

    /**
     * @return array|string
     */
    protected function redirectSuccess()
    {
        return $this->redirectSuccess;
    }

    /**
     * @return array|string
     */
    protected function redirectError()
    {
        return $this->redirectError;
    }
}
