<?php

namespace ServerManager\LaravelServerManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'auth_type' => ['required', 'in:password,key'],
            'password' => ['nullable', 'string'],
            'private_key' => ['nullable', 'string'],
            'private_key_password' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['string']
        ];

        // Make name unique for create/update
        if ($this->isMethod('post')) {
            $rules['name'][] = 'unique:servers,name';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['name'][] = Rule::unique('servers', 'name')->ignore($this->route('server'));
        }

        // Conditional validation based on auth type
        if ($this->input('auth_type') === 'password') {
            $rules['password'][] = 'required';
        } elseif ($this->input('auth_type') === 'key') {
            $rules['private_key'][] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Server name is required.',
            'name.unique' => 'A server with this name already exists.',
            'host.required' => 'Server host/IP address is required.',
            'username.required' => 'Username is required.',
            'auth_type.required' => 'Authentication type is required.',
            'auth_type.in' => 'Authentication type must be either password or key.',
            'password.required' => 'Password is required when using password authentication.',
            'private_key.required' => 'Private key is required when using key authentication.',
            'port.integer' => 'Port must be a valid number.',
            'port.min' => 'Port must be at least 1.',
            'port.max' => 'Port must not exceed 65535.'
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'port' => $this->port ?: 22,
        ]);
    }

    protected function passedValidation(): void
    {
        // Clean up fields based on auth type
        if ($this->input('auth_type') === 'password') {
            $this->merge([
                'private_key' => null,
                'private_key_password' => null
            ]);
        } elseif ($this->input('auth_type') === 'key') {
            $this->merge([
                'password' => null
            ]);
        }
    }
}