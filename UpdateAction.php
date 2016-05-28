<?php

/**
 * Author: Pavel Petrov <itnelo@gmail.com>
 * Date: 11.02.16 15:26
 */

namespace yii\tools\crud;

use Yii;

/**
 * Class UpdateAction
 * @package yii\tools\crud
 */
class UpdateAction extends Action
{
    /** @inheritdoc */
    public $view = 'update';

    /** @inheritdoc */
    public $validation = true;

    /** @inheritdoc */
    public $modelAction = 'save';

    /** @inheritdoc */
    public $scenario = 'update';

    /** @inheritdoc */
    public $redirectSuccess = null;

    /** @inheritdoc */
    public $redirectError = null;

    /**
     * Action for default redirect behavior (if $redirectSuccess if not configured)
     * @var string
     */
    public $redirectAction = null;

    /** @inheritdoc */
    protected $type = 'update';

    /**
     * @inheritdoc
     */
    public function init()
    {
        // @todo Yii::t() yii2-crud
        if (!isset($this->flashSuccess)) {
            $this->flashSuccess = Yii::t('yii2-crud', 'Item(s) successfully updated');
        }

        if (!isset($this->flashError)) {
            $this->flashError = Yii::t('yii2-crud', 'Item(s) update failed');
        }

        if (!isset($this->redirectAction)) {
            $this->redirectAction = $this->id;
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function redirectSuccess()
    {
        return empty($this->redirectSuccess) ? $this->refresh() : parent::redirectSuccess();
    }

    /**
     * @inheritdoc
     */
    protected function redirectError()
    {
        return empty($this->redirectError) ? $this->refresh() : parent::redirectError();
    }

    /**
     * @return array
     */
    protected function refresh()
    {
        $searchValue = $this->multiple ? $this->model[0]->{$this->searchKey} : $this->model->{$this->searchKey};
        return [$this->redirectAction, $this->requestKey => $searchValue];
    }
}
