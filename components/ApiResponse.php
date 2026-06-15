<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

/**
 * ApiResponse provides standardized AJAX response arrays for all controllers.
 *
 * All JSON responses follow this envelope:
 * ```json
 * {
 *     "status":  "success" | "error",
 *     "message": "Human-readable result message",
 *     "errors":  { "field": ["msg"] },   // only on validation error
 *     ...                                // any extra data via $data
 * }
 * ```
 *
 * ## Usage
 *
 * ```php
 * return ApiResponse::success('Expense saved.');
 * return ApiResponse::success('Income created.', ['id' => $model->id]);
 * return ApiResponse::error('Validation failed.', $model->errors);
 * return ApiResponse::error('Not found.');
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ApiResponse
{
    /**
     * Build a success response envelope.
     *
     * @param string $message Human-readable success message.
     * @param array  $data    Optional extra keys merged into the envelope (e.g. ['id' => 1]).
     * @return array
     */
    public static function success(string $message, array $data = []): array
    {
        return array_merge(['status' => 'success', 'message' => $message], $data);
    }

    /**
     * Build an error response envelope.
     *
     * @param string $message Human-readable error message.
     * @param array  $errors  Optional Yii2 model errors keyed by attribute name.
     * @param array  $data    Optional extra keys merged into the envelope.
     * @return array
     */
    public static function error(string $message, array $errors = [], array $data = []): array
    {
        $response = array_merge(['status' => 'error', 'message' => $message], $data);
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        return $response;
    }
}
