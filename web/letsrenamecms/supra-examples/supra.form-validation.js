/*
 * Following functionality is not yet implemented 
 */

//Add validation directly on input
form.input.name.addValidationRules(Y.Form.Validation.notEmpty);
form.input.email.addValidationRules(Y.Form.Validation.notEmpty, Y.Form.Validation.isEmail);

// or user can set all rules at once

form.setValidationRules({
    'name': [Y.Form.Validation.notEmpty],
    'email': [Y.Form.Validation.notEmpty, Y.Form.Validation.isEmail]
});


//If more complex validation is needed, user can bind to 'validate' event

form.on('validate', function (rules, errors) {
    //...
});


/*
 * Custom validation
 */

form.input.confirm.addValidationRules({
    'fn': function (value, form, rule) {
        if (value != form.inputs.password.getValue()) {
            return false;
        }
    },
    'message': '...'
});

// or user can do same on 'validate' event

form.on('validate', function (values, inputs) {
    var val_password = this.inputs.password.getValue();
    var val_confirm  = this.inputs.confirm.getvalue();

    if (val_password != val_confirm) {
        this.inputs.confirm.showError('...');
        return false;
    }
});