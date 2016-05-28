<?php

/**
 * Author: Pavel Petrov <itnelo@gmail.com>
 * Date: 11.02.16 15:26
 */

namespace yii\tools\crud;

use Yii;
use yii\tools\crud\traits\RedirectTrait;

class CreateAction extends Action
{
    use RedirectTrait;

    /** @inheritdoc */
    public $view = 'create';

    /** @inheritdoc */
    public $validation = true;

    /** @inheritdoc */
    public $modelAction = 'save';

    /** @inheritdoc */
    public $redirectSuccess = '';

    /** @inheritdoc */
    public $redirectError = '';

    /**
     * Action for default redirect behavior (if $redirectSuccess if not configured)
     * @var string
     */
    public $redirectAction = 'update';

    /**
     * Action for default error redirect behavior (if $redirectError if not configured)
     * @var string
     */
    public $redirectErrorAction = 'create';

    /**
     * Redirect params that will be passed into route config.
     * If present, it will overwrite default [requestKey => searchValue].
     * @var array|callable
     */
    public $redirectParams = [];

    /**
     * @var array
     */
    public $redirectErrorParams = [];

    /** @inheritdoc */
    public $scenario = 'create';

    /** @inheritdoc */
    protected $type = 'create';

    /** @inheritdoc */
    public $modelSearchPolicy = self::MODEL_SEARCH_POLICY_NONE;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // @todo Yii::t() yii2-crud
        if (!isset($this->flashSuccess)) {
            $this->flashSuccess = Yii::t('yii2-crud', 'Item(s) successfully created');
        }

        if (!isset($this->flashError)) {
            $this->flashError = Yii::t('yii2-crud', 'Item(s) creation failed');
        }

        parent::init();
    }
}
