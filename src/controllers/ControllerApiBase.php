<?php
/**
 * Base controller for API without authentication
 */

namespace whotrades\rds\controllers;

class ControllerApiBase extends Controller
{
    /**
     * @return array
     */
    public function behaviors()
    {
        return [];
    }
}
