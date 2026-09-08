<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class JumuishiUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        $provision = $this->routeIs('jumuishi.users.provision');

        return [
            'global_user_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email', 'max:255'],
            'event_uuid' => [$provision ? 'nullable' : 'required', 'uuid'],
            'event_type' => $provision
                ? ['nullable', 'in:user.created']
                : ['required', 'in:password.changed,user.updated,user.disabled,user.enabled'],
            'first_name' => [$provision ? 'required' : 'required_if:event_type,user.updated', 'string', 'max:100'],
            'second_name' => ['nullable', 'string', 'max:100'],
            'last_name' => [$provision ? 'required' : 'required_if:event_type,user.updated', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'status' => [$provision ? 'required' : 'required_if:event_type,user.updated', 'in:active,disabled,locked,pending'],
            'password_hash' => [
                'bail',
                $provision ? 'required' : 'required_if:event_type,password.changed',
                'string', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (password_get_info((string) $value)['algoName'] === 'unknown') {
                        $fail('The password hash format is unsupported.');
                    }
                },
            ],
        ];
    }
}
