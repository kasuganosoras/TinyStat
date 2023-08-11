<?php
include(__DIR__ . "/../config.php");
include(__DIR__ . "/../includes/functions.php");
include(__DIR__ . "/../includes/database.php");
include(__DIR__ . "/../includes/parsedown.php");
Header("Content-Type: text/javascript; charset=utf-8");
$pdo = Database::getConnection();
$config = GetSiteConfig();
SESSION_START();
?>
var globalStatus = 'normal';
var tmpStatus = 'normal';
var hoverItem = null;
var isLogged = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;
var serviceEdit = `<div class="row text-left no-margin create-service">
        <div class="col-sm-6">
            <div class="form-group">
                <label><?php _UE('edit.service.name'); ?></label>
                <input type="text" class="form-control" id="service_name" placeholder="<?php _UE('edit.service.name'); ?>" required>
                <small class="form-text text-muted"><?php _UE('edit.service.name.desc'); ?></small>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label><?php _UE('edit.service.type'); ?></label>
                <select class="form-control" id="service_type">
                    <option value="http">HTTP</option>
                    <option value="tcp">TCP</option>
                    <option value="udp">UDP</option>
                    <option value="icmp">ICMP</option>
                </select>
                <small class="form-text text-muted"><?php _UE('edit.service.type.desc'); ?></small>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label><?php _UE('edit.service.host'); ?></label>
                <input type="text" class="form-control" id="service_host" placeholder="<?php _UE('edit.service.host'); ?>" required>
                <small class="form-text text-muted"><?php _UE('edit.service.host.desc'); ?></small>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label><?php _UE('edit.service.port'); ?></label>
                <input type="number" class="form-control" id="service_port" placeholder="<?php _UE('edit.service.port'); ?>">
                <small class="form-text text-muted"><?php _UE('edit.service.port.desc'); ?></small>
            </div>
        </div>
    </div>
    <div class="http-service-options">
        <div class="separator"><?php _UE('edit.service.http.optional'); ?></div>
        <div class="row text-left no-margin create-service">
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php _UE('edit.service.status'); ?></label>
                    <input type="number" class="form-control" id="service_status" placeholder="<?php _UE('edit.service.status'); ?>">
                    <small class="form-text text-muted"><?php _UE('edit.service.status.desc'); ?></small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php _UE('edit.service.response'); ?></label>
                    <input type="text" class="form-control" id="service_response" placeholder="<?php _UE('edit.service.response'); ?>">
                    <small class="form-text text-muted"><?php _UE('edit.service.response.desc'); ?></small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php _UE('edit.service.ssl_verify'); ?></label>
                    <select class="form-control" id="service_ssl_verify">
                        <option value="1"><?php _UE('edit.service.yes'); ?></option>
                        <option value="0"><?php _UE('edit.service.no'); ?></option>
                    </select>
                    <small class="form-text text-muted"><?php _UE('edit.service.ssl_verify.desc'); ?></small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php _UE('edit.service.method'); ?></label>
                    <select class="form-control" id="service_method">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                        <option value="HEAD">HEAD</option>
                        <option value="OPTIONS">OPTIONS</option>
                        <option value="PATCH">PATCH</option>
                    </select>
                    <small class="form-text text-muted"><?php _UE('edit.service.method.desc'); ?></small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php _UE('edit.service.data'); ?></label>
                    <textarea class="form-control" id="service_data" placeholder="<?php _UE('edit.service.data'); ?>"></textarea>
                    <small class="form-text text-muted"><?php _UE('edit.service.data.desc'); ?></small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label><?php _UE('edit.service.headers'); ?></label>
                    <textarea class="form-control" id="service_headers" placeholder="<?php _UE('edit.service.headers'); ?>"></textarea>
                    <small class="form-text text-muted"><?php _UE('edit.service.headers.desc'); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>`;

function CreateChart(id, name, data) {
    var statusColorList = {
        normal: "#2fcc66",
        warning: "#e9a420",
        error: "#e92020",
        unknown: "#cccccc"
    }
    var statusTextList = {
        normal: "<?php _UE('status.label.normal'); ?>",
        warning: "<?php _UE('status.label.warning'); ?>",
        error: "<?php _UE('status.label.error'); ?>",
        unknown: "<?php _UE('status.label.unknown'); ?>"
    }
    var statusPercentList = {
        normal: 0,
        warning: 0,
        error: 0
    }
    var chartSvgHtml = '';
    var currentStatus = 'normal';
    var padding = 0;
    var currentDate = new Date();
    currentDate = currentDate.getFullYear().toString() + '.' + (currentDate.getMonth() + 1).toString() + '.' + currentDate.getDate().toString();
    for (var i = 0; i < 90 - data.length; i++) {
        var date = CalculateDate(currentDate, -(89 - i));
        chartSvgHtml += `<rect height="34" width="3" x="${padding * 5}" y="0" fill="#cccccc" class="uptime-day uptime-no-data day-${i}" data-date="${date}" data-incident="<?php _UE('chart.no.data'); ?>" tabindex="0"></rect>`;
        padding++;
    }
    for (var i = 0; i < data.length; i++) {
        var day = data[i] || {
            status: 'unknown'
        };
        var dayStatus = day.status;
        var dayIncident = day.incident || '<?php _UE('chart.no.incident'); ?>';
        var dayStatusColor = statusColorList[dayStatus];
        chartSvgHtml += `<rect height="34" width="3" x="${padding * 5}" y="0" fill="${dayStatusColor}" data-date="${day.date}" data-incident="${dayIncident}" class="uptime-day day-${i}"></rect>`;
        currentStatus = dayStatus;
        statusPercentList[dayStatus]++;
        padding++;
    }
    var statusColor = statusColorList[currentStatus];
    var statusText = statusTextList[currentStatus];
    var statusPercent = (statusPercentList.normal / data.length * 100).toFixed(1);
    var chartHtml = `<div id="service-${id}">
        <span class="service-name">
            <span>${name}</span>&nbsp;&nbsp;
            <?php echo isset($_SESSION['user']) ? '<span title="' . _U('chart.edit.service') . '" class="force-link text-small hover-text" onclick="EditService(${id});"><i class="fas fa-edit"></i></span>&nbsp;' : ''; ?>
            <?php echo isset($_SESSION['user']) ? '<span title="' . _U('chart.delete.service') . '" class="force-link text-small hover-text" onclick="DeleteService(${id});"><i class="fas fa-trash"></i></span>' : ''; ?>
        </span>
        <span class="component-status status-text-${currentStatus}">${statusText}</span>
        <div class="shared-partial uptime-90-days-wrapper">
            <svg class="availability-time-line-graphic" preserveAspectRatio="none" height="34" tabindex="0" viewBox="0 0 448 34">${chartSvgHtml}</svg>
            <div class="legend legend-group">
                <div class="legend-item light legend-item-date-range">
                    <span class="availability-time-line-legend-day-count">90</span> <?php _UE('chart.days.ago'); ?>
                </div>
                <div class="spacer"></div>
                <div class="legend-item legend-item-uptime-value">
                    <span class="uptime-percent">${statusPercent}</span> % <?php _UE('chart.online'); ?>
                </div>
                <div class="spacer"></div>
                <div class="legend-item light legend-item-date-range"><?php _UE('chart.today'); ?></div>
            </div>
        </div>
    </div>`;
    if (currentStatus == 'warning') {
        if (globalStatus == 'normal') {
            UpdateGlobalStatus('warning');
        }
        tmpStatus = 'warning';
    }
    if (currentStatus == 'error') {
        UpdateGlobalStatus('error');
        tmpStatus = 'error';
    }
    $(`#service-${id}`).remove();
    $(".service-container").append(chartHtml);
    $(".uptime-day").on('mouseover', function() {
        var date = $(this).data('date');
        var incident = $(this).data('incident');
        $(".floating-text").html(`<b>${date}</b><br><span>${incident}</span>`);
        $(".floating-text").show();
        var x = $(this).offset().left;
        var y = $(this).offset().top;
        $(".floating-text").css('left', x - 150);
        $(".floating-text").css('top', y - $(".floating-text").height() - 38);
    });
    $(".uptime-day").on('mouseout', function() {
        $(".floating-text").hide();
    });
}

function UpdateGlobalStatus(status) {
    var statusTextList = {
        normal: "<?php _UE('status.text.normal'); ?>",
        warning: "<?php _UE('status.text.warning'); ?>",
        error: "<?php _UE('status.text.error'); ?>"
    }
    var statusIconList = {
        normal: "fas fa-check-circle",
        warning: "fas fa-exclamation-triangle",
        error: "fas fa-times-circle"
    }
    $(".status-container").removeClass('status-normal').removeClass('status-warning').removeClass('status-error');
    $(".status-container").addClass('status-' + status);
    $(".status-container .status-text").html(`<i class="${statusIconList[status]}"></i>&nbsp;&nbsp;${statusTextList[status]}`);
}

function CalculateDate(from, days) {
    var split = from.split('.');
    var year = parseInt(split[0]);
    var month = parseInt(split[1]) - 1;
    var day = parseInt(split[2]);
    var date = new Date(year, month, day);
    date.setDate(date.getDate() + days);
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    month = month < 10 ? '0' + month.toString() : month.toString();
    day = day < 10 ? '0' + day.toString() : day.toString();
    date = year.toString() + '.' + month + '.' + day;
    return date;
}

function CreateIncident(date, incident) {
    var html = `<div class="incident-item" data-date="${date}">
        <div class="incident-time">${date}</div>
        <div class="incident-text"<?php echo isset($_SESSION['user']) ? ' title="点击编辑"' : ''; ?>>${incident}</div>
    </div>`;
    $(".incidents-container").append(html);
}

function RefreshData() {
    $.ajax({
        url: '?action=getLogs',
        async: true,
        dataType: 'json',
        success: function(data) {
            var services = data;
            tmpStatus = 'normal';
            for (var id in services) {
                var service = services[id];
                var name = service.name;
                var data = service.data;
                CreateChart(id, name, data);
            }
            if (tmpStatus == 'normal') {
                UpdateGlobalStatus('normal');
            }
        },
        error: function() {
            Swal.fire({
                title: '<?php _UE('alert.error'); ?>',
                text: '<?php _UE('alert.error.network'); ?>',
                icon: 'error',
                confirmButtonText: '<?php _UE('alert.confirm'); ?>'
            });
        }
    });
    LoadIncidents(1);
}

function LoadIncidents(page) {
    $.ajax({
        url: '?action=getIncidents&page=' + page,
        async: true,
        dataType: 'json',
        success: function(data) {
            var incidents = data.incidents;
            $(".incidents-container").html('');
            for (var i = 0; i < 10; i++) {
                var date = CalculateDate(data.date, -i);
                var incident = incidents[date] || '<?php _UE('chart.no.incident'); ?>';
                CreateIncident(date, incident);
            }
            if (isLogged) {
                $(".incident-item").on('click', function() {
                    var date = $(this).data('date');
                    var incident = incidents[date] || '<?php _UE('chart.no.incident'); ?>';
                    EditIncident(date, incident);
                });
            }
            var pages = data.pages;
            if (pages > 1) {
                var html = '<div class="text-center"><ul class="pagination">';
                for (var i = 1; i <= pages; i++) {
                    html += `<li class="${i == page ? 'active' : ''}"><a href="javascript:LoadIncidents(${i});">${i}</a></li>`;
                }
                html += '</ul></div>';
                $(".incidents-container").append(html);
            }
        },
        error: function() {
            Swal.fire({
                title: '<?php _UE('alert.error'); ?>',
                text: '<?php _UE('alert.error.network'); ?>',
                icon: 'error',
                confirmButtonText: '<?php _UE('alert.confirm'); ?>'
            });
        }
    });
}

function EditIncident(date) {
    var currentIncident = '';
    $.ajax({
        url: '?action=getIncident&date=' + date,
        async: false,
        dataType: 'json',
        success: function(data) {
            currentIncident = data.incident || '';
        },
        error: function() {
            Swal.fire({
                title: '<?php _UE('alert.error'); ?>',
                text: '<?php _UE('alert.error.network'); ?>',
                icon: 'error',
                confirmButtonText: '<?php _UE('alert.confirm'); ?>'
            });
        }
    });
    Swal.fire({
        title: '<?php _UE('alert.edit.incident.title'); ?>',
        html: '<textarea class="form-control incident-edit" id="incident" placeholder="<?php _UE('alert.edit.incident.desc'); ?>" required>' + currentIncident + '</textarea>',
        width: 800,
        confirmButtonText: '<?php _UE('alert.edit.incident.confirm'); ?>',
        showCancelButton: true,
        cancelButtonText: '<?php _UE('alert.edit.incident.cancel'); ?>',
        showLoaderOnConfirm: true,
        preConfirm: function() {
            var incident = $("#incident").val();
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '?action=editIncident',
                    type: 'POST',
                    data: {
                        date: date,
                        incident: incident
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 200) {
                            resolve();
                        } else {
                            reject(data.message);
                        }
                    },
                    error: function() {
                        reject('<?php _UE('alert.error.network'); ?>');
                    }
                });
            });
        },
        allowOutsideClick: false
    }).then(function() {
        window.location.reload();
    }).catch(function(error) {
        Swal.fire({
            title: '<?php _UE('alert.error'); ?>',
            text: error,
            icon: 'error',
            confirmButtonText: '<?php _UE('alert.confirm'); ?>'
        });
    });
}

function EditBroadcast() {
    var currentBroadcast = '';
    $.ajax({
        url: '?action=getBroadcast',
        async: false,
        dataType: 'json',
        success: function(data) {
            currentBroadcast = data.broadcast;
        },
        error: function() {
            Swal.fire({
                title: '<?php _UE('alert.error'); ?>',
                text: '<?php _UE('alert.error.network'); ?>',
                icon: 'error',
                confirmButtonText: '<?php _UE('alert.confirm'); ?>'
            });
        }
    });
    Swal.fire({
        title: '<?php _UE('alert.edit.broadcast.title'); ?>',
        html: '<textarea class="form-control broadcast-edit" id="broadcast" placeholder="<?php _UE('alert.edit.broadcast.desc'); ?>" required>' + currentBroadcast + '</textarea>',
        width: 800,
        confirmButtonText: '<?php _UE('alert.edit.broadcast.confirm'); ?>',
        showCancelButton: true,
        cancelButtonText: '<?php _UE('alert.edit.broadcast.cancel'); ?>',
        showLoaderOnConfirm: true,
        preConfirm: function() {
            var broadcast = $("#broadcast").val();
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '?action=editBroadcast',
                    type: 'POST',
                    data: {
                        broadcast: broadcast
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 200) {
                            resolve();
                        } else {
                            reject(data.message);
                        }
                    },
                    error: function() {
                        reject('<?php _UE('alert.error.network'); ?>');
                    }
                });
            });
        },
        allowOutsideClick: false
    }).then(function() {
        window.location.reload();
    }).catch(function(error) {
        Swal.fire({
            title: '<?php _UE('alert.error'); ?>',
            text: error,
            icon: 'error',
            confirmButtonText: '<?php _UE('alert.confirm'); ?>'
        });
    });
}

function CreateService() {
    Swal.fire({
        title: '<?php _UE('alert.add.service.title'); ?>',
        html: serviceEdit,
        width: 800,
        confirmButtonText: '<?php _UE('alert.add.service.confirm'); ?>',
        showCancelButton: true,
        cancelButtonText: '<?php _UE('alert.add.service.cancel'); ?>',
        showLoaderOnConfirm: true,
        preConfirm: function() {
            var name = $("#service_name").val();
            var type = $("#service_type").val();
            var host = $("#service_host").val();
            var port = $("#service_port").val();
            var status = $("#service_status").val();
            var response = $("#service_response").val();
            var ssl_verify = $("#service_ssl_verify").val();
            var method = $("#service_method").val();
            var data = $("#service_data").val();
            var headers = $("#service_headers").val();
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '?action=createService',
                    type: 'POST',
                    data: {
                        name: name,
                        type: type,
                        host: host,
                        port: port,
                        status: status,
                        response: response,
                        ssl_verify: ssl_verify,
                        method: method,
                        data: data,
                        headers: headers
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 200) {
                            resolve();
                        } else {
                            reject(data.message);
                        }
                    },
                    error: function() {
                        reject('<?php _UE('alert.error.network'); ?>');
                    }
                });
            });
        },
        allowOutsideClick: false
    }).then(function() {
        window.location.reload();
    }).catch(function(error) {
        Swal.fire({
            title: '<?php _UE('alert.error'); ?>',
            text: error,
            icon: 'error',
            confirmButtonText: '<?php _UE('alert.confirm'); ?>'
        });
    });
    $("#service_type").on('change', function() {
        var type = $(this).val();
        if (type == 'http') {
            $(".http-service-options").show();
        } else {
            $(".http-service-options").hide();
        }
    });
}

function EditService(id) {
    var serviceData = {};
    $.ajax({
        url: "?action=getService&id=" + id,
        async: false,
        dataType: 'json',
        success: function(data) {
            serviceData = data.service;
        },
        error: function() {
            Swal.fire({
                title: '<?php _UE('alert.error'); ?>',
                text: '<?php _UE('alert.error.network'); ?>',
                icon: 'error',
                confirmButtonText: '<?php _UE('alert.confirm'); ?>',
            });
        }
    });
    Swal.fire({
        title: '<?php _UE('alert.edit.service.title'); ?>',
        html: serviceEdit,
        width: 800,
        confirmButtonText: '<?php _UE('alert.edit.service.confirm'); ?>',
        showCancelButton: true,
        cancelButtonText: '<?php _UE('alert.edit.service.cancel'); ?>',
        showLoaderOnConfirm: true,
        didOpen: function() {
            $("#service_name").val(serviceData.name);
            $("#service_type").val(serviceData.type);
            $("#service_host").val(serviceData.host);
            $("#service_port").val(serviceData.port);
            $("#service_status").val(serviceData.status);
            $("#service_response").val(serviceData.response);
            $("#service_ssl_verify").val(serviceData.extra.ssl_verify);
            $("#service_method").val(serviceData.extra.method);
            $("#service_data").val(serviceData.extra.data);
            $("#service_headers").val(serviceData.extra.headers);
            if (serviceData.type == 'http') {
                $(".http-service-options").show();
            } else {
                $(".http-service-options").hide();
            }
        },
        preConfirm: function() {
            var name = $("#service_name").val();
            var type = $("#service_type").val();
            var host = $("#service_host").val();
            var port = $("#service_port").val();
            var status = $("#service_status").val();
            var response = $("#service_response").val();
            var ssl_verify = $("#service_ssl_verify").val();
            var method = $("#service_method").val();
            var data = $("#service_data").val();
            var headers = $("#service_headers").val();
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '?action=editService',
                    type: 'POST',
                    data: {
                        id: id,
                        name: name,
                        type: type,
                        host: host,
                        port: port,
                        status: status,
                        response: response,
                        ssl_verify: ssl_verify,
                        method: method,
                        data: data,
                        headers: headers
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 200) {
                            resolve();
                        } else {
                            reject(data.message);
                        }
                    },
                    error: function() {
                        reject('<?php _UE('alert.error.network'); ?>');
                    }
                });
            });
        },
        allowOutsideClick: false
    }).then(function() {
        window.location.reload();
    }).catch(function(error) {
        Swal.fire({
            title: '<?php _UE('alert.error'); ?>',
            text: error,
            icon: 'error',
            confirmButtonText: '<?php _UE('alert.confirm'); ?>'
        });
    });
    $("#service_type").on('change', function() {
        var type = $(this).val();
        if (type == 'http') {
            $(".http-service-options").show();
        } else {
            $(".http-service-options").hide();
        }
    });
}

function DeleteService(id) {
    Swal.fire({
        title: '<?php _UE('alert.delete.service.title'); ?>',
        text: '<?php _UE('alert.delete.service.desc'); ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<?php _UE('alert.delete.service.confirm'); ?>',
        cancelButtonText: '<?php _UE('alert.delete.service.cancel'); ?>',
        showLoaderOnConfirm: true,
        preConfirm: function() {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '?action=deleteService',
                    type: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 200) {
                            resolve();
                        } else {
                            reject(data.message);
                        }
                    },
                    error: function() {
                        reject('<?php _UE('alert.error.network'); ?>');
                    }
                });
            });
        },
        allowOutsideClick: false
    }).then(function() {
        window.location.reload();
    }).catch(function(error) {
        Swal.fire({
            title: '<?php _UE('alert.error'); ?>',
            text: error,
            icon: 'error',
            confirmButtonText: '<?php _UE('alert.confirm'); ?>'
        });
    });
}

function Login() {
    var username = null;
    var password = null;
    Swal.fire({
        title: '<?php _UE('alert.login.title'); ?>',
        html: '<input type="text" class="form-control" id="username" placeholder="<?php _UE('alert.login.username'); ?>" required><br><input type="password" class="form-control" id="password" placeholder="<?php _UE('alert.login.password'); ?>" required>',
        confirmButtonText: '<?php _UE('alert.login.confirm'); ?>',
        showCancelButton: true,
        cancelButtonText: '<?php _UE('alert.login.cancel'); ?>',
        showLoaderOnConfirm: true,
        preConfirm: function() {
            username = $("#username").val();
            password = $("#password").val();
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '?action=login',
                    type: 'POST',
                    data: {
                        username: username,
                        password: password
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 200) {
                            resolve();
                        } else {
                            reject(data.message);
                        }
                    },
                    error: function() {
                        reject('<?php _UE('alert.error.network'); ?>');
                    }
                });
            });
        },
        allowOutsideClick: false
    }).then(function() {
        window.location.reload();
    }).catch(function(error) {
        Swal.fire({
            title: '<?php _UE('alert.error'); ?>',
            text: error,
            icon: 'error',
            confirmButtonText: '<?php _UE('alert.confirm'); ?>'
        });
    });
}

function Logout() {
    Swal.fire({
        title: '<?php _UE('alert.logout.title'); ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<?php _UE('alert.logout.confirm'); ?>',
        cancelButtonText: '<?php _UE('alert.logout.cancel'); ?>',
        showLoaderOnConfirm: true,
        preConfirm: function() {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '?action=logout',
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == 200) {
                            resolve();
                        } else {
                            reject(data.message);
                        }
                    },
                    error: function() {
                        reject('<?php _UE('alert.error.network'); ?>');
                    }
                });
            });
        },
        allowOutsideClick: false
    }).then(function() {
        window.location.reload();
    }).catch(function(error) {
        Swal.fire({
            title: '<?php _UE('alert.error'); ?>',
            text: error,
            icon: 'error',
            confirmButtonText: '<?php _UE('alert.confirm'); ?>'
        });
    });
}

$(document).ready(function() {
    RefreshData();
    setInterval(RefreshData, 30000);
});