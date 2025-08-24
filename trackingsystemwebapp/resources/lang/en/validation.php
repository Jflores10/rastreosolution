<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => 'The :attribute must be accepted.',
    'active_url'           => 'The :attribute is not a valid URL.',
    'after'                => 'The :attribute must be a date after :date.',
    'alpha'                => 'The :attribute may only contain letters.',
    'alpha_dash'           => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num'            => 'The :attribute may only contain letters and numbers.',
    'array'                => 'The :attribute must be an array.',
    'before'               => 'The :attribute must be a date before :date.',
    'between'              => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file'    => 'The :attribute must be between :min and :max kilobytes.',
        'string'  => 'The :attribute must be between :min and :max characters.',
        'array'   => 'The :attribute must have between :min and :max items.',
    ],
    'boolean'              => 'The :attribute field must be true or false.',
    'confirmed'            => 'The :attribute confirmation does not match.',
    'date'                 => 'The :attribute is not a valid date.',
    'date_format'          => 'The :attribute does not match the format :format.',
    'different'            => 'The :attribute and :other must be different.',
    'digits'               => 'The :attribute must be :digits digits.',
    'digits_between'       => 'The :attribute must be between :min and :max digits.',
    'dimensions'           => 'The :attribute has invalid image dimensions.',
    'distinct'             => 'The :attribute field has a duplicate value.',
    'email'                => 'The :attribute must be a valid email address.',
    'exists'               => 'The selected :attribute is invalid.',
    'file'                 => 'The :attribute must be a file.',
    'filled'               => 'The :attribute field is required.',
    'image'                => 'The :attribute must be an image.',
    'in'                   => 'The selected :attribute is invalid.',
    'in_array'             => 'The :attribute field does not exist in :other.',
    'integer'              => 'The :attribute must be an integer.',
    'ip'                   => 'The :attribute must be a valid IP address.',
    'json'                 => 'The :attribute must be a valid JSON string.',
    'max'                  => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file'    => 'The :attribute may not be greater than :max kilobytes.',
        'string'  => 'The :attribute may not be greater than :max characters.',
        'array'   => 'The :attribute may not have more than :max items.',
    ],
    'mimes'                => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => 'The :attribute must be at least :min.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'string'  => 'The :attribute must be at least :min characters.',
        'array'   => 'The :attribute must have at least :min items.',
    ],
    'not_in'               => 'The selected :attribute is invalid.',
    'numeric'              => 'The :attribute must be a number.',
    'present'              => 'The :attribute field must be present.',
    'regex'                => 'The :attribute format is invalid.',
    'required'             => 'The :attribute field is required.',
    'required_if'          => 'The :attribute field is required when :other is :value.',
    'required_unless'      => 'The :attribute field is required unless :other is in :values.',
    'required_with'        => 'The :attribute field is required when :values is present.',
    'required_with_all'    => 'The :attribute field is required when :values is present.',
    'required_without'     => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same'                 => 'The :attribute and :other must match.',
    'size'                 => [
        'numeric' => 'The :attribute must be :size.',
        'file'    => 'The :attribute must be :size kilobytes.',
        'string'  => 'The :attribute must be :size characters.',
        'array'   => 'The :attribute must contain :size items.',
    ],
    'string'               => 'The :attribute must be a string.',
    'timezone'             => 'The :attribute must be a valid zone.',
    'unique'               => 'The :attribute has already been taken.',
    'url'                  => 'The :attribute format is invalid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'email' => [
            'required' => '* El nombre de usuario es requerido.',
            'email' => 'El formato del correo electrónico es incorrecto.',
            'unique' => 'El nombre de usuario se encuentra en uso, por favor ingrese otro.',
        ],

        'cedula' => [
            'required' => '* La cédula es requerida.',
            'numeric' => 'Debe ingresar un valor numérico.',
            'digits' => 'Debe ingresar 10 dígitos.',
            'unique' => 'La cédula ha sido ingresada previamente.',
        ],

        'nombre' => [
            'required' => '* El nombre es requerido.',
            'max' => 'Debe ingresar máximo 150 caracteres.',
        ],

        'descripcion' => [
            'required' => '* La descripción es requerida.',
            'max' => 'Debe ingresar máximo 255 caracteres.',
        ],

        'multa_tubo' => [
            'numeric' => 'Debe ingresar un valor numérico.',
        ],

        'latitud' => [
            'required' => '* La latitud es requerida.',
            'numeric' => 'Debe ingresar un valor numérico.',
        ],

        'longitud' => [
            'required' => '* La longitud es requerida.',
            'numeric' => 'Debe ingresar un valor numérico.',
        ],

        'radio' => [
            'required' => '* El radio es requerido.',
            'numeric' => 'Debe ingresar un valor numérico.',
        ],

        'placa' => [
            'required' => '* La placa es requerida.',
            'unique' => 'La placa ha sido ingresada previamente.',
        ],

        'cooperativa_id' => [
            'required' => '* La cooperativa es requerida.',
        ],

        'marca' => [
            'required' => '* La marca es requerida.',
            'max' => 'Debe ingresar máximo 100 caracteres.',
        ],

        'modelo' => [
            'required' => '* El modelo es requerido.',
            'max' => 'Debe ingresar máximo 100 caracteres.',
        ],

        'serie' => [
            'required' => '* La serie es requerida.',
            'max' => 'Debe ingresar máximo 50 caracteres.',
        ],

        'motor' => [
            'required' => '* El motor es requerido.',
            'max' => 'Debe ingresar máximo 50 caracteres.',
        ],

        'tipo_unidad_id' => [
            'required' => '* El tipo de unidad es requerido.',
        ],

        'tipo_usuario_id' => [
            'required' => '* El tipo de usuario es requerido.',
        ],

        'name' => [
            'required' => '* El nombre es requerido.',
            'max' => 'Debe ingresar máximo 150 caracteres.',
        ],

        'password' => [
            'required' => '* La contraseña es requerida.',
            'min' => 'La contraseña debe tener mínimo 6 caracteres.',
            'confirmed' => 'Las contraseñas no coinciden.',
        ],


          'password_confirmation' => [
                'required' => '* La confirmación de contraseña es requerida.',
            ],

        'contraseña_actual' => [
            'required' => '* La contraseña actual es requerida.',
        ],

        'nombre_usuario' => [
            'required' => '* El nombre de usuario es requerido.',
            'max' => 'Debe ingresar máximo 150 caracteres.',
            'unique' => 'El nombre de usuario ya existe, ingrese otro.',
        ],


        'unidad_id' =>[
            'required' => '* La unidad es requerida.',
        ],

        'fecha_inicio' =>[
            'required' => '* La fecha inicial es requerida.',
        ],

        'fecha_fin' =>[
            'required' => '* La fecha final es requerida.',
        ],

        'hora_salida' =>[
            'required' => '* La hora de salida es requerida.',
        ],

        'email_alarma' =>[
            'required' => '* El email de alarma es requerido.',
            'email' => "El formato del email es incorrecto.",
        ],

        'imei' =>[
            'required' => '* El IMEI  es requerido.',
            'unique' => 'El IMEI ya está siendo utilizado por otra unidad.',
        ],

        'pdi' =>[
            'required' => '* El PDI  es requerido.',
        ],
















    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
