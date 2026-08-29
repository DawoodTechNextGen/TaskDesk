<?php
// permissions.php
// Single place that defines the user roles and what each one is allowed to do.
// Role values live in `users`.`user_role` and are mirrored in $_SESSION['user_role'].

define('ROLE_ADMIN', 1);
define('ROLE_INTERN', 2);
define('ROLE_SUPERVISOR', 3);
define('ROLE_MANAGER', 4);
// Per-module read/write observer - see `module_permissions` below for what that
// actually means. Only an Admin can create, edit or delete one.
define('ROLE_COLLABORATOR', 5);

/* =========================
   Collaborator module permissions
   =========================
   A Collaborator's access is not one blanket on/off switch - an Admin grants
   them "no access" / "read" / "write" separately for each module below, stored
   in `module_permissions` (user_id, module, access). Every other role keeps its
   existing, separately-coded permissions and never consults this table. */

define('MODULE_TASKS', 'tasks');
define('MODULE_REGISTRATIONS', 'registrations');
define('MODULE_CURRICULUM', 'curriculum');
define('MODULE_INTERNS', 'interns');
define('MODULE_ATTENDANCE', 'attendance');
define('MODULE_REPORTS', 'reports');

// Single source of truth for the permission checkboxes in User Management and
// for validating module names coming back from that form.
if (!function_exists('collaboratorModules')) {
    function collaboratorModules()
    {
        return [
            MODULE_TASKS         => 'Tasks',
            MODULE_REGISTRATIONS => 'Registrations',
            MODULE_CURRICULUM    => 'Curriculum',
            MODULE_INTERNS       => 'Interns (Active / Frozen / Completed / Freeze requests)',
            MODULE_ATTENDANCE    => 'Intern Attendance',
            MODULE_REPORTS       => 'Reports',
        ];
    }
}

// Maps a page's filename to the module it belongs to, so a page or its footer
// script can ask "does this Collaborator have access to what's on THIS page".
// Pages not listed here (Dashboard, and everything Admin/Manager-only) aren't
// gated by module permissions at all.
if (!function_exists('currentPageModule')) {
    function currentPageModule()
    {
        static $map = [
            'tasks.php'                  => MODULE_TASKS,
            'tasks_create.php'           => MODULE_TASKS,
            'registrations.php'          => MODULE_REGISTRATIONS,
            'registrations_new.php'      => MODULE_REGISTRATIONS,
            'registrations_contact.php'  => MODULE_REGISTRATIONS,
            'registrations_interview.php' => MODULE_REGISTRATIONS,
            'registrations_rejected.php' => MODULE_REGISTRATIONS,
            'curriculum.php'             => MODULE_CURRICULUM,
            'internees.php'              => MODULE_INTERNS,
            'frozen_interns.php'         => MODULE_INTERNS,
            'completed_interns.php'      => MODULE_INTERNS,
            'freeze_management.php'      => MODULE_INTERNS,
            'attendance_supervisor.php'  => MODULE_ATTENDANCE,
            'reports.php'                => MODULE_REPORTS,
        ];
        $file = basename($_SERVER['SCRIPT_NAME'] ?? '');
        return $map[$file] ?? null;
    }
}

// 'none' | 'read' | 'write'. Non-Collaborator roles are unaffected by this table,
// so they always get 'write' here - their real access is decided elsewhere, by role.
if (!function_exists('moduleAccess')) {
    function moduleAccess($module)
    {
        if (currentUserRole() !== ROLE_COLLABORATOR) {
            return 'write';
        }
        return $_SESSION['module_permissions'][$module] ?? 'none';
    }
}

if (!function_exists('canViewModule')) {
    function canViewModule($module)
    {
        return in_array(moduleAccess($module), ['read', 'write'], true);
    }
}

if (!function_exists('canWriteModule')) {
    function canWriteModule($module)
    {
        return moduleAccess($module) === 'write';
    }
}

// All permission rows currently on file for one user, e.g. ['tasks' => 'write'].
// A module missing from the result means "no access".
if (!function_exists('getModulePermissionsForUser')) {
    function getModulePermissionsForUser($conn, $user_id)
    {
        $perms = [];
        $stmt = $conn->prepare("SELECT module, access FROM module_permissions WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $perms[$row['module']] = $row['access'];
        }
        $stmt->close();
        return $perms;
    }
}

// Replaces a user's whole permission set with $permissions (e.g. ['tasks' => 'write',
// 'reports' => 'read']). Unknown module names and anything other than read/write are
// dropped rather than stored, so a tampered form field can't grant a bogus module.
if (!function_exists('saveModulePermissionsForUser')) {
    function saveModulePermissionsForUser($conn, $user_id, array $permissions)
    {
        $user_id = (int)$user_id;
        $valid = collaboratorModules();
        $conn->query("DELETE FROM module_permissions WHERE user_id = " . $user_id);
        $stmt = $conn->prepare("INSERT INTO module_permissions (user_id, module, access) VALUES (?, ?, ?)");
        foreach ($permissions as $module => $access) {
            if (!array_key_exists($module, $valid)) {
                continue;
            }
            if ($access !== 'read' && $access !== 'write') {
                continue;
            }
            $stmt->bind_param('iss', $user_id, $module, $access);
            $stmt->execute();
        }
        $stmt->close();
    }
}

if (!function_exists('roleLabel')) {
    function roleLabel($role)
    {
        switch ((int)$role) {
            case ROLE_ADMIN:
                return 'Admin';
            case ROLE_INTERN:
                return 'Intern';
            case ROLE_SUPERVISOR:
                return 'Supervisor';
            case ROLE_MANAGER:
                return 'Manager';
            case ROLE_COLLABORATOR:
                return 'Collaborator';
            default:
                return 'Unknown';
        }
    }
}

if (!function_exists('currentUserRole')) {
    function currentUserRole()
    {
        return isset($_SESSION['user_role']) ? (int)$_SESSION['user_role'] : 0;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return currentUserRole() === ROLE_ADMIN;
    }
}

// Roles that may never write. Kept as a list so more read-only roles can be added later.
if (!function_exists('isReadOnlyRole')) {
    function isReadOnlyRole($role = null)
    {
        $role = ($role === null) ? currentUserRole() : (int)$role;
        return in_array($role, [ROLE_COLLABORATOR], true);
    }
}

/* =========================
   Page guards
========================= */

// Send anyone whose role is not listed back to their own dashboard.
if (!function_exists('requirePageRoles')) {
    function requirePageRoles(array $roles)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        if (!in_array(currentUserRole(), $roles, true)) {
            header('Location: index.php');
            exit;
        }
    }
}

if (!function_exists('requirePageAdmin')) {
    function requirePageAdmin()
    {
        requirePageRoles([ROLE_ADMIN]);
    }
}

// Call alongside a page's existing role check. A no-op for every role except
// Collaborator, who gets sent home if they don't have at least read access to $module.
if (!function_exists('requirePageModule')) {
    function requirePageModule($module)
    {
        if (currentUserRole() === ROLE_COLLABORATOR && !canViewModule($module)) {
            header('Location: index.php');
            exit;
        }
    }
}

/* =========================
   Controller guards
========================= */

// Controllers read their action from the query string, the form body or a JSON body,
// so the guard has to look in all three.
if (!function_exists('requestedAction')) {
    function requestedAction()
    {
        $action = $_REQUEST['action'] ?? '';
        if ($action === '') {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $json = json_decode($raw, true);
                if (is_array($json) && isset($json['action'])) {
                    $action = $json['action'];
                }
            }
        }
        return is_string($action) ? $action : '';
    }
}

if (!function_exists('denyJson')) {
    function denyJson($message)
    {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}

// Called at the top of a controller with that controller's read-only actions.
// A read-only role may run those and nothing else.
if (!function_exists('enforceReadOnlyAccess')) {
    function enforceReadOnlyAccess(array $readOnlyActions)
    {
        if (!isReadOnlyRole()) {
            return;
        }
        if (in_array(requestedAction(), $readOnlyActions, true)) {
            return;
        }
        denyJson('Read-only access: collaborators cannot make changes.');
    }
}

if (!function_exists('requireAdminAction')) {
    function requireAdminAction()
    {
        if (!isAdmin()) {
            denyJson('Unauthorized: only an Admin can perform this action.');
        }
    }
}

// A no-op for every role except Collaborator, who needs at least read access to $module.
if (!function_exists('requireModuleView')) {
    function requireModuleView($module)
    {
        if (currentUserRole() === ROLE_COLLABORATOR && !canViewModule($module)) {
            denyJson('You do not have access to this module.');
        }
    }
}

// A no-op for every role except Collaborator, who needs write access to $module.
if (!function_exists('requireModuleWrite')) {
    function requireModuleWrite($module)
    {
        if (currentUserRole() === ROLE_COLLABORATOR && !canWriteModule($module)) {
            denyJson(canViewModule($module)
                ? 'Read-only access: you cannot make changes to this module.'
                : 'You do not have access to this module.');
        }
    }
}

// Drop-in replacement for enforceReadOnlyAccess(): call once at the top of a
// controller with the module it belongs to and the list of actions in that file
// that write. Everything else in the file is treated as read. A no-op for every
// role except Collaborator.
if (!function_exists('enforceModuleAccess')) {
    function enforceModuleAccess($module, array $writeActions = [])
    {
        if (currentUserRole() !== ROLE_COLLABORATOR) {
            return;
        }
        if (in_array(requestedAction(), $writeActions, true)) {
            requireModuleWrite($module);
        } else {
            requireModuleView($module);
        }
    }
}
