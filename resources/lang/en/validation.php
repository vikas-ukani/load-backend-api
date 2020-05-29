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

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal :value.',
        'file' => 'The :attribute must be greater than or equal :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'key_must_be_key1_or_key2' => "The :key must :key1 or :key2",
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal :value.',
        'file' => 'The :attribute must be less than or equal :value kilobytes.',
        'string' => 'The :attribute must be less than or equal :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'present' => 'The :attribute field must be present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobytes.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid zone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute format is invalid.',
    'uuid' => 'The :attribute must be a valid UUID.',

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
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],
    'common' => [
        'saved' => ':module successfully saved.',


        'created' => ':module successfully created.',
        'updated' => ':module successfully updated.',
        'deleted' => 'Record successfully deleted.',
        'removed' => ':module successfully removed.',
        'module_deleted' => ':module successfully deleted.',

        'added' => ':module successfully added.',
        'already_added' => ':module has already added.',

        'generated' => ":module successfully generated",
        'error_generated' => "Error in :module generate.",

        'details_not_found' => ':module details not found.',
        'details_found' => ':module details retrieve successfully.',

        'invalid_user' => 'You are not valid user.',

        'max_length' => ':key must be :max digit long.',

        'key_not_exist' => ':key dose not exist in our records',

        'your_account_deleted_by_admin' => 'Your account has been deleted by admin.',

        'select_any_value' => "Please, Select any :module",

        'email_not_exist' => ':key dose not exist in our records',
        'email_already_exist' => ':key already exist in our records',
        'key_already_exist' => ':key already exist in our records',

        'must_be_unique' => ":key must be unique record.",
        'email_password_not_match' => "Email or password are not match in our system.",
        'invalid_cridentials' => 'Invalid credentials',
        'invalid_key1_key2' => 'Invalid :key1 and :key2',
        'unauthorized_user' => 'Unauthorized user.',
        'token_expired_login_again' => "The token has been expired, try login",
        'token_invalid' => "The token is invalid",
        'token_required_in_header' => "The token is required in header.",
        'new_refresh_token' => "New token generated.",
        'not_register_user' => "Sorry, You are not register user.",
        'login_success' => 'Successfully logged in.',
        'failed_to_login' => 'Failed to login, please try again.',

        'logout_success' => 'User successfully logged out.',
        'logout_failed' => 'Failed to logout, please try again.',

        'old_password_not_match' => "Old Password Not Match.",
        'forgot_password_email_send' => "We send you email to reset your password.",
        'password_changed_success' => "Your password has been successfully changed.",

        'error_in_file_upload' => "Error in file upload, try again.",
        'file_success_upload' => "File successfully upload.",

        'successfully_activated_account' => "Congratulation, Your account has been successfully activated.",
        'already_account_activated' => "You are already Activated your account",

        "please_give_key" => "please, give a :key",
        "invalid_key" => "please, give a valid :key",

        "please_upgrade_your_account_to" => "Please, Upgrade your account to :account",

        'successfully_followed' => "User successfully followed",
        'successfully_unfollowed' => "User successfully unfollowed",

        'professional_profile_is_in_snooze_mode' => "This professional user currently in snooze mode, you can not contact with this user",

        'feed_liked' => "Feed successfully liked",
        'feed_unlike' => "Feed successfully unlike",

        'image_not_found' => "Image not found",


        'can_not_create_program_to_this_date' => "you can not create Preset.it is already created as same date.",
        'can_not_create_request_for_two_times_only' => "you can not create other request. it is already created :number times.",

        'key_not_found' => ":key not found",

    ],

    "exceptions" => [
        "method_not_allow_exception" => "Method is not allowed for the requested route.",
        "invalid_route" => "Invalid route.",
    ],
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
     */

    'attributes' => [],

];
