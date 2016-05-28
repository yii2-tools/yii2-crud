<?php

/**
 * Author: Pavel Petrov <itnelo@gmail.com>
 * Date: 11.02.16 15:26
 */

namespace yii\tools\crud;

use Yii;
use yii\tools\crud\traits\RedirectTrait;

/**
 * Class DeleteAction
 * @package yii\tools\crud
 */
class DeleteAction extends Action
{
    use RedirectTrait;

    /** @inheritdoc */
    public $view = 'index';

    /** @inheritdoc */
    public $modelAction = 'delete';

    /** @inheritdoc */
    public $scenario = 'delete';

    /** @inheritdoc */
    public $redirectSuccess = '';

    /** @inheritdoc */
    public $redirectError = '';

    /**
     * Action for default redirect behavior (if $redirectSuccess if not configured)
     * @var string
     */
    public $redirectAction = 'index';

    /**
     * Action for default error redirect behavior (if $redirectError if not configured)
     * @var string
     */
    public $redirectErrorAction = 'index';

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
    protected $type = 'delete';

    /** @inheritdoc */
    public $modelLoadPolicy = self::MODEL_LOAD_POLICY_NONE;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // @todo Yii::t() yii2-crud
        if (!isset($this->flashSuccess)) {
            $this->flashSuccess = Yii::t('yii2-crud', 'Item(s) successfully removed');
        }

        if (!isset($this->flashError)) {
            $this->flashError = Yii::t('yii2-crud', 'Item(s) remove failed');
        }

        parent::init();
    }
}
