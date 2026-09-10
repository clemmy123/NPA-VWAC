!function ($) {
    "use strict";

    function FormWizard() {}

    FormWizard.prototype.createBasic = function (form) {
        form.children("div").steps({
            headerTag: "h3",
            bodyTag: "section",
            transitionEffect: "slideLeft",
            onFinishing: function (event, currentIndex) {
                console.log("Form has been validated!");
                return true;
            },
            onFinished: function (event, currentIndex) {
                console.log("Form can be submitted using submit method. E.g. $('#basic-form').submit()");
                $("#basic-form").submit();
            }
        });
        return form;
    };

    FormWizard.prototype.createValidatorForm = function (form) {
        form.validate({
            errorPlacement: function (error, element) {
                element.after(error);
            }
        });

        form.children("div").steps({
            headerTag: "h3",
            bodyTag: "section",
            transitionEffect: "slideLeft",
            onStepChanging: function (event, currentIndex, newIndex) {
                form.validate().settings.ignore = ":disabled,:hidden";

                if (!form.valid()) {
                    return false;
                }

                return FormWizard.prototype.checkCustomStepRequirements(form, currentIndex, newIndex);
            },
            onFinishing: function (event, currentIndex) {
                form.validate().settings.ignore = ":disabled";
                return form.valid();
            },
            onFinished: function (event, currentIndex) {
                $('#review-finish-btn').trigger('click');
            }
        });

        return form;
    };

    // Step-two (index 1) requires a victim to have been added before moving forward,
    // unless the reporter has marked the case as anonymous. victim_id is a hidden
    // field, so jQuery Validate's ":hidden" ignore rule never catches it - this
    // check fills that gap.
    FormWizard.prototype.checkCustomStepRequirements = function (form, currentIndex, newIndex) {
        var errorBanner = document.getElementById('victim-required-error');

        if (currentIndex !== 1 || newIndex <= currentIndex) {
            if (errorBanner) {
                errorBanner.style.display = 'none';
            }
            return true;
        }

        var anonymityCheckbox = document.getElementById('wants_anonymity');
        var isAnonymous = !!(anonymityCheckbox && anonymityCheckbox.checked);

        var victimId = document.getElementById('victim_id');
        var hasVictim = !!(victimId && victimId.value !== '' && victimId.value !== null);
        var canProceed = hasVictim || isAnonymous;

        if (errorBanner) {
            errorBanner.style.display = canProceed ? 'none' : 'block';

            if (!canProceed) {
                errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return canProceed;
    };

    FormWizard.prototype.createVertical = function (form) {
        form.steps({
            headerTag: "h3",
            bodyTag: "section",
            transitionEffect: "fade",
            stepsOrientation: "vertical"
        });
        return form;
    };

    FormWizard.prototype.init = function () {
        this.createBasic($("#basic-form"));
        this.createValidatorForm($("#wizard-validation-form"));
        this.createVertical($("#wizard-vertical"));
    };

    $.FormWizard = new FormWizard();
    $.FormWizard.Constructor = FormWizard;
}(window.jQuery);

!function ($) {
    "use strict";
    window.jQuery.FormWizard.init();
}(window.jQuery);
