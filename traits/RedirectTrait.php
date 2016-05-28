<?php

/**
 * Author: Pavel Petrov <itnelo@gmail.com>
 * Date: 04.05.16 14:26
 */

namespace yii\tools\crud\traits;

use Yii;

trait RedirectTrait
{
    /**
     * @inheritdoc
     */
    protected function redirectSuccess()
    {
        if (empty($this->redirectSuccess)) {
            return array_merge($this->redirectParams(), [$this->redirectAction]);
        }

        return $this->redirectSuccess;
    }

    /**
     * @inheritdoc
     */
    protected function redirectError()
    {
        if (empty($this->redirectError)) {
            return array_merge($this->redirectErrorParams(), [$this->redirectErrorAction]);
        }

        return $this->redirectError;
    }

    protected function redirectParams()
    {
        if (!empty($this->redirectParams)) {
            if (is_array($this->redirectParams)) {
                return $this->redirectParams;
            }

            if (is_callable($this->redirectParams)) {
                return call_user_func($this->redirectParams, $this);
            }

            Yii::warning("Incorrect redirect params, must be array or callable", __METHOD__);

            return [];
        }

        $searchValue = $this->multiple
            ? $this->model[0]->{$this->searchKey}
            : $this->model->{$this->searchKey};

        return [$this->requestKey => $searchValue];
    }

    protected function redirectErrorParams()
    {
        if (!empty($this->redirectErrorParams)) {
            if (is_array($this->redirectErrorParams)) {
                return $this->redirectErrorParams;
            }

            if (is_callable($this->redirectErrorParams)) {
                return call_user_func($this->redirectErrorParams, $this);
            }

            Yii::warning("Incorrect error redirect params, must be array or callable", __METHOD__);
        }

        return Yii::$app->getRequest()->get();
    }
}
