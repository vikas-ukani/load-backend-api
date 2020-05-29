<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookmark;

use Ghostff\FormRequest\RequestAbstract;

/**
 * @method array validated()
 */
class BookmarkRequest extends RequestAbstract
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required',
            'event_id' => 'required_without:professional_id',
            'professional_id' => 'required_without:event_id'

        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            //
        ];
    }
}