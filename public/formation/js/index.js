$(document).ready(function () {
    if (document.querySelector('.notifications-count') !== null) {
        loadNewNotifications();

        setInterval(function () {
            loadNewNotifications();
        }, 4000);
    }
    if (document.querySelector('#liste_modules') !== null) {
        getModules();
    }
    if (document.querySelector('#liste_cours') !== null) {
        getCourses(document.querySelector('#hidden_input_module').value, document.querySelector('#course-search-kw').value);
    }

    if (document.querySelector('#form_filter_session') !== null) {
        $('#form_filter_session').on('change', () => {
            $.ajax({
                url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
                method: "POST",
                dataType: "json",
                data: {
                    form_filter_session: $('#form_filter_session').val()
                },
                success: (r) => {
                    if ($('#subnavbar .nav-link.active').length) {
                        $('#subnavbar .nav-link.active')[0].click();
                    } else if ($('#navbar .nav-link.active').length) {
                        $('#navbar .nav-link.active')[0].click();
                    }
                }
            });
        });
    }
});

function loadNewNotifications(opened = false) {
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "POST",
        dataType: "json",
        data: {
            load_new_notifications: 1
        },
        success: function (data) {
            if (data.notifications_count > 0) {
                document.querySelector('.notifications-count').setAttribute("data-count", data.notifications_count);
            } else {
                document.querySelector('.notifications-count').removeAttribute("data-count");
            }
            document.querySelector('#notifications div').innerHTML = data.notifications;
            if (opened) {
                document.querySelector('#notifications').classList.toggle('hidden');
            }
        }
    });
}

function deleteNotification(notification_lien = "") {
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "POST",
        dataType: "json",
        data: {
            delete_notification: 1,
            lien_notification: notification_lien
        },
        success: function (data) {
            loadNewNotifications();
        }
    });
}

function showInformationsModal() {
    $(".help-resource").toggleClass("fade-in");
    if (!$(".help-resource").hasClass("fade-in")) {
        $(".help-resource").toggleClass("fade-out");
    }
}

function getModules(recherche = '') {
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "post",
        dataType: "json",
        data: {
            get_modules: 1,
            recherche: recherche
        },
        success: function (r) {
            document.querySelector('#liste_modules').innerHTML = "";
            if (r.success) {
                document.querySelector('#liste_modules').innerHTML += r.modules;
                if (document.querySelector('#form_edition_mode') && document.querySelector('#form_edition_mode').checked && window.screen.width > 768) {
                    new Sortable(document.querySelector('#liste_modules'), {
                        animation: 350,
                        chosenClass: "sortable-chosen",
                        dragClass: "sortable-drag",
                        // Element dragging ended
                        onEnd: function (/**Event*/evt) {
                            $.ajax({
                                url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
                                method: "post",
                                dataType: "json",
                                data: {
                                    set_module_position: 1,
                                    old: evt.oldIndex - 1,
                                    new: evt.newIndex - 1
                                }
                            });
                        },
                    });
                }
            }
        }
    });
}

function getCourses(module, recherche = '') {
    if (document.querySelector('#module-search-kw')) {
        recherche = document.querySelector('#module-search-kw').value;
    }
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "post",
        dataType: "json",
        data: {
            get_courses: 1,
            module: module,
            recherche: recherche
        },
        success: function (r) {
            document.querySelector('#liste_cours').innerHTML = "";
            if (r.success) {
                document.querySelector('#liste_cours').innerHTML += r.cours;
                if (document.querySelector('#form_edition_mode') && document.querySelector('#form_edition_mode').checked && window.screen.width > 768) {
                    new Sortable(document.querySelector('#liste_cours'), {
                        animation: 350,
                        chosenClass: "sortable-chosen",
                        dragClass: "sortable-drag",
                        // Element dragging ended
                        onEnd: function (/**Event*/evt) {
                            $.ajax({
                                url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
                                method: "post",
                                dataType: "json",
                                data: {
                                    set_cours_position: 1,
                                    module: module,
                                    old: evt.oldIndex - 1,
                                    new: evt.newIndex - 1
                                }
                            });
                        },
                    });
                }
            }
        }
    });
}

function showCourse(cours_id) {
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "post",
        dataType: "json",
        data: {
            show_cours: 1,
            cours_id: cours_id
        },
        success: function (r) {
            if (r.success == "ok") {
                $("#modalCourse .modal-content").html(r.modal_content);
                $("#modalCourse").modal("show");
            }
        }
    });
}

function sendTp(tp_id, input) {
    var formData = new FormData();
    formData.append("send_tp", 1);
    formData.append("tp_id", tp_id);
    formData.append("fichier", document.querySelector('#' + input).files[0]);
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "post",
        dataType: "json",
        processData: false,
        contentType: false,
        data: formData,
        success: function (r) {
            document.location.reload();
        }
    });
}

function toggleEditionMode() {
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "post",
        dataType: "json",
        data: {
            toggle_edition_mode: 1
        },
        success: function () {
            document.location.reload();
        }
    });
}

function showAddModule() {
    $.ajax({
        url: "//" + SERVER_NAME + "/erp/src/c/requests.php",
        method: "post",
        dataType: "json",
        data: {
            show_add_module: 1
        },
        success: function () {
            if (r.success == "ok") {
                $("#modalAddModule .modal-content").html(r.modal_content);
                $("#modalAddModule").modal("show");
            }
        }
    });
}