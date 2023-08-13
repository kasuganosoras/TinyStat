<?php
include(__DIR__ . "/config.php");
include(__DIR__ . "/includes/functions.php");
include(__DIR__ . "/includes/database.php");
include(__DIR__ . "/includes/parsedown.php");
$pdo      = Database::getConnection();
$config   = GetSiteConfig();
$markdown = new Parsedown();
$markdown->setSafeMode(false);
$markdown->setBreaksEnabled(true);
SESSION_START();

if (isset($_GET['action']) && is_string($_GET['action'])) {
    switch ($_GET['action']) {
        case 'getLogs':
            $services = GetServices();
            $logs = [];
            foreach ($services as $service) {
                $logs[$service['id']] = [
                    'name' => $service['name'],
                    'sort' => $service['sort'],
                    'data' => []
                ];
            }
            $stmt = $pdo->prepare('SELECT * FROM (SELECT * FROM `status` ORDER BY `id` DESC LIMIT 90) AS `t` ORDER BY `id` ASC');
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row) {
                if (isset($logs[$row['service']])) {
                    if (!isset($logs[$row['service']]['data'])) $logs[$row['service']]['data'] = [];
                    $logs[$row['service']]['data'][] = [
                        'status' => $row['status'],
                        'date' => $row['date'],
                        'incident' => json_decode($row['incident'], true) ?: []
                    ];
                }
            }
            Header("Content-Type: application/json");
            echo json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
        case 'getIncidents':
            $page = isset($_GET['page']) ? Intval($_GET['page']) : 1;
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM `incidents`');
            $stmt->execute();
            $count = $stmt->fetchColumn();
            $pages = ceil($count / 10);
            $begin = ($page - 1) * 10;
            $stmt = $pdo->prepare("SELECT * FROM `incidents` ORDER BY `date` DESC LIMIT {$begin}, 10");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $incidents = [];
            foreach ($result as $row) {
                $incidents[$row['date']] = $row['incident'] ? $markdown->text($row['incident']) : '';
            }
            Header("Content-Type: application/json");
            echo json_encode([
                'pages' => $pages,
                'date' => date('Y.m.d'),
                'incidents' => $incidents
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
        case 'getIncident':
            if (isset($_GET['date'])) {
                $date = $_GET['date'];
                $stmt = $pdo->prepare('SELECT * FROM `incidents` WHERE `date` = ?');
                $stmt->execute([$date]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result !== false) {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 200,
                        'message' => 'OK',
                        'incident' => $result['incident']
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 404,
                        'message' => 'Not Found'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 400,
                    'message' => 'Bad Request'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'editIncident':
            if (isset($_SESSION['user'])) {
                if (isset($_POST['date'], $_POST['incident'])) {
                    $date = $_POST['date'];
                    $incident = $_POST['incident'];
                    $stmt = $pdo->prepare('SELECT * FROM `incidents` WHERE `date` = ?');
                    $stmt->execute([$date]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result === false) {
                        $stmt = $pdo->prepare('INSERT INTO `incidents` (`date`, `incident`) VALUES (?, ?)');
                        $stmt->execute([$date, $incident]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE `incidents` SET `incident` = ? WHERE `date` = ?');
                        $stmt->execute([$incident, $date]);
                    }
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 200,
                        'message' => 'OK'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'getBroadcast':
            if (isset($_SESSION['user'])) {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 200,
                    'message' => 'OK',
                    'broadcast' => _C('status_description', '')
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'editBroadcast':
            if (isset($_SESSION['user'])) {
                if (isset($_POST['broadcast'])) {
                    $broadcast = $_POST['broadcast'];
                    $stmt = $pdo->prepare('SELECT * FROM `config` WHERE `key` = ?');
                    $stmt->execute(['status_description']);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result === false) {
                        $stmt = $pdo->prepare('INSERT INTO `config` (`key`, `value`) VALUES (?, ?)');
                        $stmt->execute(['status_description', $broadcast]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE `config` SET `value` = ? WHERE `key` = ?');
                        $stmt->execute([$broadcast, 'status_description']);
                    }
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 200,
                        'message' => 'OK'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'createService':
            if (isset($_SESSION['user'])) {
                if (isset($_POST['name'], $_POST['type'], $_POST['host'])) {
                    $result = CreateService($_POST);
                    Header("Content-Type: application/json");
                    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'editService':
            if (isset($_SESSION['user'])) {
                if (isset($_POST['id'], $_POST['name'], $_POST['type'], $_POST['host'])) {
                    $result = EditService($_POST);
                    Header("Content-Type: application/json");
                    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'deleteService':
            if (isset($_SESSION['user'])) {
                if (isset($_POST['id'])) {
                    $result = DeleteService($_POST['id']);
                    Header("Content-Type: application/json");
                    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'getService':
            if (isset($_SESSION['user'])) {
                if (isset($_GET['id'])) {
                    $id = Intval($_GET['id']);
                    $stmt = $pdo->prepare('SELECT * FROM `services` WHERE `id` = ?');
                    $stmt->execute([$id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result !== false) {
                        $result['extra'] = json_decode($result['extra'], true) ?: [];
                        Header("Content-Type: application/json");
                        echo json_encode([
                            'code' => 200,
                            'message' => 'OK',
                            'service' => $result
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        Header("Content-Type: application/json");
                        echo json_encode([
                            'code' => 404,
                            'message' => 'Not Found'
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'sortService':
            if (isset($_SESSION['user'])) {
                if (isset($_POST['data'])) {
                    $sorts = json_decode($_POST['data'], true);
                    for($i = 0; $i < count($sorts); $i++) {
                        $stmt = $pdo->prepare('UPDATE `services` SET `sort` = ? WHERE `id` = ?');
                        $stmt->execute([$i, $sorts[$i]]);
                    }
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 200,
                        'message' => 'OK'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'editConfig':
            if (isset($_SESSION['user'])) {
                if (isset($_POST['name'], $_POST['data']) && preg_match('/^[a-zA-Z0-9_]+$/', $_POST['name'])) {
                    $stmt = $pdo->prepare('SELECT * FROM `config` WHERE `key` = ?');
                    $stmt->execute([$_POST['name']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result !== false) {
                        $stmt = $pdo->prepare('UPDATE `config` SET `value` = ? WHERE `key` = ?');
                        $stmt->execute([$_POST['data'], $_POST['name']]);
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO `config` (`key`, `value`) VALUES (?, ?)');
                        $stmt->execute([$_POST['name'], $_POST['data']]);
                    }
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 200,
                        'message' => 'OK'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    Header("Content-Type: application/json");
                    echo json_encode([
                        'code' => 400,
                        'message' => 'Bad Request'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                Header("Content-Type: application/json");
                echo json_encode([
                    'code' => 403,
                    'message' => 'Forbidden'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'login':
            if (isset($_POST['username'], $_POST['password'])) {
                $username = $_POST['username'];
                $password = $_POST['password'];
                $user = GetUserByUsername($username);
                if ($user === false) {
                    $user = GetUserByEmail($username);
                }
                if ($user !== false) {
                    if (password_verify($password . $user['salt'], $user['password'])) {
                        $_SESSION['user'] = $user;
                        Header("Content-Type: application/json");
                        echo json_encode([
                            'code' => 200,
                            'message' => 'OK'
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        exit;
                    }
                }
            }
            Header("Content-Type: application/json");
            echo json_encode([
                'code' => 403,
                'message' => 'Invalid username or password'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
        case 'logout':
            unset($_SESSION['user']);
            Header("Content-Type: application/json");
            echo json_encode([
                'code' => 200,
                'message' => 'OK'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
        default:
            Header("Content-Type: application/json");
            echo json_encode([
                'code' => 404,
                'message' => 'Not Found'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php _CE('site_title', _U('default.title')); ?> - <?php _CE('site_description', _U('default.description')); ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome/css/all.css">
    <link rel="stylesheet" href="themes/<?php echo _E('THEME'); ?>.css">
    <link rel="shortcut icon" href="themes/favicon.ico">
</head>

<body>
    <div class="floating-text"></div>
    <div class="container">
        <div class="col-sm-1"></div>
        <div class="col-sm-10">
            <div class="col-sm-8">
                <h1 class="editable" data-name="site_title"><?php _CE('site_title', _U('default.title')); ?></h1>
                <h4 class="editable" data-name="site_description"><?php _CE('site_description', _U('default.description')); ?></h4>
            </div>
            <div class="col-sm-4 text-right">
                <button class="btn btn-primary btn-block top-button" onclick="RefreshData();"><i class="fas fa-sync-alt"></i>&nbsp;&nbsp;<?php _UE('button.refresh'); ?></button>
            </div>
            <div class="col-sm-12">
                <div class="status-container status-normal">
                    <span class="status-text"><i class="fas fa-check-circle"></i>&nbsp;&nbsp;<?php _UE('status.text.normal'); ?></span>
                    <?php $statusDescription = _C('status_description');
                    if ($statusDescription) {
                        $statusDescription = $markdown->text($statusDescription);
                        echo "<div class=\"status-description\">{$statusDescription}</div>";
                    }
                    ?>
                </div>
            </div>
            <div class="col-sm-12">
                <p class="text-right">
                    <?php echo isset($_SESSION['user']) ? '<a href="javascript:CreateService();"><i class="fas fa-plus"></i>&nbsp;&nbsp;' . _U('button.add.service') . '</a>&nbsp;&nbsp;' : ''; ?>
                    <?php echo isset($_SESSION['user']) ? '<a href="javascript:EditBroadcast();"><i class="fas fa-edit"></i>&nbsp;&nbsp;' . _U('button.edit.broadcast') . '</a>' : ''; ?>
                </p>
                <div class="service-container"></div>
            </div>
            <div class="col-sm-12 margin-top-32">
                <h3><?php _UE('incidents.list'); ?></h3>
                <div class="incidents-container"></div>
            </div>
            <div class="col-sm-12">
                <hr>
                <div class="float-left center-on-mobile">
                    <?php
                    if (isset($_SESSION['user'])) {
                        $user = $_SESSION['user'];
                        $username = htmlspecialchars($user['username']);
                        $prefix = _U('text.logged_in_as');
                        $logoutButton = _U('button.logout');
                        echo "{$prefix} {$username}&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"javascript:Logout();\"><i class=\"fas fa-sign-out-alt\"></i>&nbsp;{$logoutButton}</a>";
                    } else {
                        $prefix = _U('text.not_logged_in');
                        $loginButton = _U('button.login');
                        echo "{$prefix}&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"javascript:Login();\"><i class=\"fas fa-sign-in-alt\"></i>&nbsp;{$loginButton}</a>";
                    }
                    ?>
                </div>
                <div class="float-right center-on-mobile">
                    Powered by <a href="https://github.com/kasuganosoras/TinyStat" target="_blank">TinyStat</a>&nbsp;&nbsp;|&nbsp;&nbsp;Make with <i class="fas fa-heart status-text-error"></i>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/sweetalert2.all.min.js"></script>
<script src="js/Sortable.min.js"></script>
<script src="js/jquery-sortable.js"></script>
<script src="js/main.js.php"></script>

</html>