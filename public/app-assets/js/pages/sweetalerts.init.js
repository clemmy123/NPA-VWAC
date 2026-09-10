!function ($) {
    "use strict";

    var T = window.translations || {};
    var SA = T.sweetalert || {};
    var MD = T.modal || {};
    var MSG = T.messages || {};
    var SR = T.sector_reports || {};

    function tr(group, key, fallback) {
        if (group && group[key]) {
            return group[key];
        }

        return fallback;
    }

    function nativeForm(form) {
        return form && form.jquery ? form.get(0) : form;
    }

    function otherCheckboxesFor(input) {
        var group = input.closest("[data-sector-option-group]");

        if (!group) {
            return [];
        }

        return Array.prototype.slice.call(group.querySelectorAll('input[type="checkbox"][data-allows-other="1"]'));
    }

    function syncSectorOtherInput(input) {
        var hasOtherText = input.value.trim() !== "";
        var otherBoxes = otherCheckboxesFor(input);

        if (hasOtherText && otherBoxes.length) {
            otherBoxes[0].checked = true;
        }

        input.required = otherBoxes.some(function (box) {
            return box.checked;
        });
    }

    function syncSectorOtherFields(form) {
        form = nativeForm(form);

        if (!form) {
            return;
        }

        form.querySelectorAll('[data-sector-other-input="1"]').forEach(function (input) {
            syncSectorOtherInput(input);
        });
    }

    function validateSectorOtherFields(form) {
        form = nativeForm(form);

        if (!form) {
            return true;
        }

        var firstInvalid = null;

        form.querySelectorAll('[data-sector-other-input="1"]').forEach(function (input) {
            syncSectorOtherInput(input);

            var otherSelected = otherCheckboxesFor(input).some(function (box) {
                return box.checked;
            });

            if (otherSelected && input.value.trim() === "") {
                input.classList.add("is-invalid");
                input.setCustomValidity(tr(SR, "specify_other_required", "Tafadhali taja nyingine."));
                firstInvalid = firstInvalid || input;
            } else {
                input.classList.remove("is-invalid");
                input.setCustomValidity("");
            }
        });

        if (firstInvalid) {
            if (firstInvalid.reportValidity) {
                firstInvalid.reportValidity();
            }

            firstInvalid.focus();
            return false;
        }

        return true;
    }

    window.syncSectorOtherFields = syncSectorOtherFields;
    window.validateSectorOtherFields = validateSectorOtherFields;

    var SweetAlert = function () {};

    SweetAlert.prototype.init = function () {
        $(document).on("input", '[data-sector-other-input="1"]', function () {
            syncSectorOtherInput(this);
        });

        $(document).on("change", 'input[type="checkbox"][data-allows-other="1"]', function () {
            var group = this.closest("[data-sector-option-group]");
            var input = group ? group.querySelector('[data-sector-other-input="1"]') : null;

            if (input) {
                syncSectorOtherInput(input);
            }
        });

        $("#sa-basic").on("click", function () {
            Swal.fire({
                title: tr(SA, "basic_title", "Kompyuta inaweza kutumiwa na mtu yeyote!"),
                confirmButtonColor: "#348cd4"
            });
        });

        $("#sa-title").click(function () {
            Swal.fire({
                title: tr(SA, "internet_title", "Intaneti?"),
                text: tr(SA, "internet_text", "Bado ipo?"),
                type: "question",
                confirmButtonColor: "#348cd4"
            });
        });

        $("#sa-success").click(function () {
            Swal.fire({
                title: tr(SA, "success_title", "Mafanikio!"),
                text: tr(SA, "success_message", "Umefanikiwa kubofya kitufe."),
                type: "success",
                confirmButtonColor: "#348cd4"
            });
        });

        $("#sa-error").click(function () {
            Swal.fire({
                type: "error",
                title: tr(SA, "error_title", "Hitilafu"),
                text: tr(SA, "error_message", "Kitu kimeenda vibaya."),
                confirmButtonColor: "#348cd4"
            });
        });

        $("#sa-long-content").click(function () {
            Swal.fire({
                imageUrl: "https://placeholder.pics/svg/300x1500",
                imageHeight: 1500,
                imageAlt: tr(SA, "tall_image", "Picha ndefu"),
                confirmButtonColor: "#348cd4"
            });
        });

        $("#sa-custom-position").click(function () {
            Swal.fire({
                position: "top-end",
                type: "success",
                title: tr(SA, "saved_successfully", "Taarifa zimehifadhiwa kwa mafanikio."),
                showConfirmButton: false,
                timer: 1500
            });
        });

        $("#sa-warning").click(function () {
            var url = this.getAttribute("data-url");

            Swal.fire({
                title: tr(MSG, "swal_commit_title", tr(SA, "are_you_sure", "Una uhakika?")),
                text: tr(MSG, "swal_commit_text", "Kushughulikia ujumbe huu hakuwezi kutenduliwa."),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#348cd4",
                cancelButtonColor: "#6c757d",
                confirmButtonText: tr(MSG, "swal_commit_confirm", "Ndiyo, shughulikia!"),
                cancelButtonText: tr(SA, "cancel", "Katisha")
            }).then(function (result) {
                if (result.value) {
                    fetch(url, {
                        method: "PATCH",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            "Content-Type": "application/json"
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    tr(MSG, "swal_committed_title", "Imefanikiwa!"),
                                    data.message || tr(SA, "success_message", "Taarifa zimehifadhiwa kwa mafanikio."),
                                    "success"
                                ).then(() => {
                                    window.location.href = data.redirect;
                                });
                            } else {
                                Swal.fire(
                                    tr(SA, "error", "Hitilafu"),
                                    data.message || tr(SA, "error_message", "Kitu kimeenda vibaya."),
                                    "error"
                                );
                            }
                        })
                        .catch(function () {
                            Swal.fire(
                                tr(SA, "error", "Hitilafu"),
                                tr(MSG, "swal_error_commit", "Hitilafu imetokea wakati wa kushughulikia ujumbe."),
                                "error"
                            );
                        });
                }
            });
        });

        $("#sa-invalid").click(function () {
            var url = this.getAttribute("data-url");

            Swal.fire({
                title: tr(SA, "are_you_sure", "Una uhakika?"),
                text: tr(SA, "mark_invalid_text", "Kuweka ujumbe huu kuwa batili kunahitaji sababu."),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: tr(SA, "yes_proceed", "Ndiyo, endelea!"),
                cancelButtonText: tr(SA, "cancel", "Katisha")
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: tr(SA, "invalid_reason_title", "Sababu ya kuweka kuwa batili"),
                        input: "textarea",
                        inputLabel: tr(SA, "invalid_reason_label", "Tafadhali andika sababu"),
                        inputPlaceholder: tr(SA, "invalid_reason_placeholder", "Andika sababu hapa..."),
                        inputAttributes: {
                            "aria-label": tr(SA, "invalid_reason_aria", "Sababu ya kubatilisha"),
                            "maxlength": 1000,
                            "required": true
                        },
                        showCancelButton: true,
                        confirmButtonText: tr(SA, "submit", "Wasilisha"),
                        cancelButtonText: tr(SA, "cancel", "Katisha"),
                        confirmButtonColor: "#d33",
                        showLoaderOnConfirm: true,
                        preConfirm: function (reason) {
                            if (!reason) {
                                Swal.showValidationMessage(tr(SA, "reason_required", "Sababu inahitajika."));
                                return false;
                            }

                            return fetch(url, {
                                method: "PATCH",
                                headers: {
                                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({ reason: reason })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data.success) {
                                        throw new Error(data.message || tr(SA, "failed_invalid", "Imeshindwa kuweka kuwa batili."));
                                    }

                                    return data;
                                })
                                .catch(error => {
                                    Swal.showValidationMessage(
                                        tr(SA, "request_failed", "Ombi limeshindwa:") + " " + error.message
                                    );
                                });
                        },
                        allowOutsideClick: function () {
                            return !Swal.isLoading();
                        }
                    }).then(function (result) {
                        if (result.value) {
                            Swal.fire({
                                title: tr(SA, "success", "Mafanikio!"),
                                text: result.value.message || tr(SA, "success_message", "Imefanikiwa."),
                                type: "success",
                                confirmButtonColor: "#348cd4"
                            }).then(() => {
                                window.location.href = result.value.redirect;
                            });
                        }
                    });
                }
            });
        });

        $("#review-finish-btn").click(function () {
            var form = $("#wizard-validation-form");
            var url = form.attr("action");

            if (!validateSectorOtherFields(form)) {
                return;
            }

            Swal.fire({
                title: tr(SA, "are_you_sure", "Una uhakika?"),
                text: tr(SA, "confirm_submit_message", "Mara baada ya kuwasilisha, ripoti yako itahifadhiwa. Thibitisha kuendelea au katisha ili kukagua zaidi."),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#348cd4",
                cancelButtonColor: "#6c757d",
                confirmButtonText: tr(SA, "confirm", "Thibitisha"),
                cancelButtonText: tr(SA, "cancel", "Katisha")
            }).then(function (result) {
                if (result.value) {
                    if (!validateSectorOtherFields(form)) {
                        return;
                    }

                    Swal.fire({
                        title: tr(SA, "submitting", "Inawasilisha..."),
                        text: tr(SA, "submitting_message", "Tafadhali subiri wakati ripoti yako inahifadhiwa."),
                        allowOutsideClick: false,
                        onBeforeOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    fetch(url, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            "X-Requested-With": "XMLHttpRequest",
                            "Accept": "application/json"
                        },
                        body: new FormData(nativeForm(form))
                    })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();

                            if (data.success) {
                                Swal.fire({
                                    title: tr(SA, "success_title", "Mafanikio!"),
                                    text: data.message || tr(SA, "success_message", "Ripoti yako imewasilishwa kwa mafanikio."),
                                    type: "success",
                                    confirmButtonColor: "#348cd4"
                                }).then(() => {
                                    window.location.href = data.redirect || "/reports";
                                });
                            } else {
                                var errorHtml = "<div>" + (data.message || tr(SA, "error_message", "Kitu kimeenda vibaya.")) + "</div>";

                                if (data.errors && typeof data.errors === "object") {
                                    var items = Object.keys(data.errors).map(function (field) {
                                        var fieldMessages = Array.isArray(data.errors[field]) ? data.errors[field].join(" ") : data.errors[field];
                                        var label = field.replace(/\.\d+$/, "").replace(/_ids?$/, "").replace(/[_.]/g, " ").replace(/\b\w/g, function (c) { return c.toUpperCase(); });
                                        return "<li>" + label + ": " + fieldMessages + "</li>";
                                    });

                                    if (items.length) {
                                        errorHtml += '<ul class="text-start mb-0 mt-2">' + items.join("") + "</ul>";
                                    }
                                }

                                Swal.fire({
                                    title: tr(SA, "error_title", "Hitilafu"),
                                    html: errorHtml,
                                    type: "error",
                                    confirmButtonColor: "#348cd4"
                                });
                            }
                        })
                        .catch(error => {
                            Swal.close();

                            Swal.fire({
                                title: tr(SA, "error_title", "Hitilafu"),
                                text: tr(SA, "error_submitting", "Hitilafu imetokea wakati wa kuwasilisha.") + " " + error.message,
                                type: "error",
                                confirmButtonColor: "#348cd4"
                            });
                        });
                }
            });
        });

        $("#sa-params").click(function () {
            Swal.fire({
                title: tr(SA, "are_you_sure", "Una uhakika?"),
                text: tr(SA, "delete_confirm_text", "Kitendo hiki hakiwezi kutenduliwa."),
                type: "warning",
                showCancelButton: true,
                confirmButtonText: tr(SA, "yes_delete", "Ndiyo, futa!"),
                cancelButtonText: tr(SA, "no_cancel", "Hapana, katisha!"),
                confirmButtonClass: "btn btn-success mt-2",
                cancelButtonClass: "btn btn-danger ml-2 mt-2",
                buttonsStyling: false
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: tr(SA, "deleted_title", "Imefutwa!"),
                        text: tr(SA, "deleted_text", "Faili yako imefutwa."),
                        type: "success"
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: tr(SA, "cancelled_title", "Imeghairiwa"),
                        text: tr(SA, "cancelled_text", "Taarifa zako ziko salama."),
                        type: "error"
                    });
                }
            });
        });

        $("#sa-image").click(function () {
            Swal.fire({
                title: tr(SA, "app_title", "Mfumo"),
                text: tr(SA, "app_description", "Mfumo wa Usimamizi"),
                imageUrl: "assets/images/logo-sm.png",
                imageHeight: 50,
                animation: false,
                confirmButtonColor: "#348cd4"
            });
        });

        $("#sa-close").click(function () {
            var timerInterval;

            Swal.fire({
                title: tr(SA, "auto_close_title", "Taarifa itajifunga yenyewe!"),
                html: tr(SA, "auto_close_html", "Itafunga baada ya <strong></strong> sekunde."),
                timer: 2000,
                onBeforeOpen: function () {
                    Swal.showLoading();

                    timerInterval = setInterval(function () {
                        Swal.getContent().querySelector("strong").textContent = Swal.getTimerLeft();
                    }, 100);
                },
                onClose: function () {
                    clearInterval(timerInterval);
                }
            });
        });

        $("#custom-html-alert").click(function () {
            Swal.fire({
                title: tr(SA, "html_example_title", "<i>HTML</i> <u>mfano</u>"),
                type: "info",
                html: tr(SA, "html_example_text", "Unaweza kutumia <b>maandishi mazito</b>, viungo na tagi nyingine za HTML"),
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonColor: "#348cd4",
                cancelButtonColor: "#f1556c",
                confirmButtonText: '<i class="mdi mdi-thumb-up-outline"></i> ' + tr(SA, "great", "Vizuri!"),
                cancelButtonText: '<i class="mdi mdi-thumb-down-outline"></i>'
            });
        });

        $("#custom-padding-width-alert").click(function () {
            Swal.fire({
                title: tr(SA, "custom_alert_title", "Upana, nafasi na mandharinyuma maalum."),
                width: 600,
                padding: 100,
                confirmButtonColor: "#348cd4",
                background: "#fff url(//subtlepatterns2015.subtlepatterns.netdna-cdn.com/patterns/geometry.png)"
            });
        });

        $("#ajax-alert").click(function () {
            Swal.fire({
                title: tr(SA, "github_username_title", "Weka jina lako la mtumiaji la Github"),
                input: "text",
                inputAttributes: {
                    autocapitalize: "off"
                },
                showCancelButton: true,
                confirmButtonText: tr(SA, "look_up", "Tafuta"),
                confirmButtonColor: "#348cd4",
                cancelButtonColor: "#6c757d",
                showLoaderOnConfirm: true,
                preConfirm: function (username) {
                    return fetch("//api.github.com/users/" + username)
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error(response.statusText);
                            }

                            return response.json();
                        })
                        .catch(function (error) {
                            Swal.showValidationMessage(
                                tr(SA, "request_failed", "Ombi limeshindwa:") + " " + error
                            );
                        });
                },
                allowOutsideClick: function () {
                    return !Swal.isLoading();
                }
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: result.value.login + " " + tr(SA, "avatar", "picha ya akaunti"),
                        imageUrl: result.value.avatar_url
                    });
                }
            });
        });

        $("#chaining-alert").click(function () {
            Swal.mixin({
                input: "text",
                confirmButtonText: tr(SA, "next", "Ifuatayo") + " →",
                showCancelButton: true,
                confirmButtonColor: "#348cd4",
                cancelButtonColor: "#6c757d",
                progressSteps: ["1", "2", "3"]
            }).queue([
                {
                    title: tr(SA, "question_1", "Swali la 1"),
                    text: tr(SA, "question_1_text", "Kuunganisha madirisha ya SweetAlert ni rahisi")
                },
                tr(SA, "question_2", "Swali la 2"),
                tr(SA, "question_3", "Swali la 3")
            ]).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: tr(SA, "all_done", "Yote yamekamilika!"),
                        html: tr(SA, "your_answers", "Majibu yako:") + " <pre><code>" + JSON.stringify(result.value) + "</code></pre>",
                        confirmButtonText: tr(SA, "lovely", "Vizuri!")
                    });
                }
            });
        });

        $("#dynamic-alert").click(function () {
            swal.queue([
                {
                    title: tr(SA, "public_ip_title", "IP yako ya umma"),
                    confirmButtonText: tr(SA, "show_public_ip", "Onyesha IP yangu ya umma"),
                    confirmButtonColor: "#348cd4",
                    text: tr(SA, "public_ip_text", "IP yako ya umma itapokelewa kupitia ombi la AJAX"),
                    showLoaderOnConfirm: true,
                    preConfirm: function () {
                        return new Promise(function (resolve) {
                            $.get("https://api.ipify.org?format=json").done(function (data) {
                                swal.insertQueueStep(data.ip);
                                resolve();
                            });
                        });
                    }
                }
            ]);
        });
    };

    $.SweetAlert = new SweetAlert();
    $.SweetAlert.Constructor = SweetAlert;
}(window.jQuery);

(function ($) {
    "use strict";
    window.jQuery.SweetAlert.init();
})(window.jQuery);
