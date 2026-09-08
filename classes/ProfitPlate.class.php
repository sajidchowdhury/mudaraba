<?php

class ProfitPlate {

    private $id;

    public function __construct($id = 'New')
    {
        // Keep behavior same as before: use admin access token (or pass explicit id if you want)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->id = $_SESSION['admin_access_token'] ?? $id;
    }

    public function SetupForm() {
        // ensure session and csrf
        if (session_status() == PHP_SESSION_NONE) session_start();
        $csrf_token = isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : '';

        // default user fields
        $user_name = $password = '';
        $user_type = $block_user = $employee_id = $status = $brunch_id = $login_start = $login_end = $role = '';

        if ($this->id !== 'New') {
            $fetch = new User();
            $data = $fetch->SingleData($this->id);
            if ($data) {
                $user_name   = htmlspecialchars($data['user_name']);
                $user_type   = htmlspecialchars($data['role']);
                $block_user  = htmlspecialchars($data['status']);
                $employee_id = htmlspecialchars($data['employee_id']);
                $status      = htmlspecialchars($data['status']);
                $brunch_id   = htmlspecialchars($data['branch_id']);
                $login_start = htmlspecialchars($data['login_start']);
                $login_end   = htmlspecialchars($data['login_end']);
                $role        = htmlspecialchars($data['role']);
                $password    = '';
            }
        }

        // instantiate user list helper
        $List = new User();

        // define menus that are always visible (no permission checkbox)
        $alwaysVisible = ['Dashboard']; // add more names if needed, e.g. 'Dashboard','Profile'

        // start rendering
        ob_start();
        ?>

        <!-- USER FORM -->
        <div class="row">
            <div class="col-md-12" style="margin-bottom: 0px!important;">
                <form id="myForm" class="login100-form validate-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="related_id" value="<?= htmlspecialchars($this->id) ?>">
                    <input type="hidden" name="PageName" id="PageName" value="Personal_Profile">
                    <input type="hidden" name="user_type" value="<?= $user_type ?>">
                    <input type="hidden" name="block_user" value="<?= $block_user ?>">
                    <input type="hidden" name="employee_id" value="<?= $employee_id ?>">
                    <input type="hidden" name="status" value="<?= $status ?>">
                    <input type="hidden" name="brunch_id" value="<?= $brunch_id ?>">
                    <input type="hidden" name="login_start" value="<?= $login_start ?>">
                    <input type="hidden" name="login_end" value="<?= $login_end ?>">
                    <input type="hidden" name="role" value="<?= $role ?>">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Entry Table</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">User Name</label>
                                                <input required type="text" class="form-control" name="user_name" id="user_name" value="<?= $user_name ?>" placeholder="Enter user name">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <input required type="text" class="form-control" name="password" id="password" value="<?= $password ?>" title="Enter password" placeholder="Enter password">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="card-footer">
                                    <input type="submit" name="kt_submit_button" id="kt_submit_button" class="btn btn-primary" value="Submit">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        // helper: recursive menu renderer that prints rows for the provided menus
        // $level used for indentation
        function renderMenuTable($menus, $List, $related_id, $level = 1, $alwaysVisible = [])
        {
            foreach ($menus as $item) {
                // children to determine deeper nesting
                $subMenus = $List->MenuByParent($item['id']);
                // if the user already has permission for this menu (exists in user_permissions)
                $hasPerm = !empty($List->getUserSingleMenus($related_id, $item['id']));
                $isChecked = $hasPerm ? 'checked' : '';

                echo '<tr data-menu-id="' . htmlspecialchars($item['id']) . '" data-parent-id="' . htmlspecialchars($item['parent_id']) . '">';
                echo '<td>' . str_repeat('&nbsp;&nbsp;&nbsp;', $level) . htmlspecialchars($item['menu_name']) . '</td>';

                if (in_array($item['menu_name'], $alwaysVisible, true)) {
                    // Always visible: show a badge and DO NOT render a checkbox
                    echo '<td><span class="badge badge-success">Always Visible</span></td>';
                } else {
                    // Regular submenu checkbox (uses same id scheme MAINMENUtodoCheck{ID})
                    echo '<td>
                            <div class="icheck-primary d-inline ml-2">
                                <input type="checkbox"
                                       id="MAINMENUtodoCheck' . htmlspecialchars($item['id']) . '"
                                       class="submenu-checkbox"
                                       data-cascade="0"
                                       data-parent-id="' . htmlspecialchars($item['parent_id']) . '"
                                       onclick="PermissionCheckUncheck(\'' . htmlspecialchars($related_id) . '\', \'' . htmlspecialchars($item['id']) . '\', \'MAINMENU\')"
                                       ' . $isChecked . '>
                                <label for="MAINMENUtodoCheck' . htmlspecialchars($item['id']) . '"></label>
                            </div>
                          </td>';
                }

                echo '</tr>';

                if (!empty($subMenus)) {
                    renderMenuTable($subMenus, $List, $related_id, $level + 1, $alwaysVisible);
                }
            }
        }

        // Fetch top-level menus (sections). Your AllMenu() returns menus with is_a_parent_id = 'Yes'
        $topMenus = $List->AllMenu();

        foreach ($topMenus as $menu) {
            // check if top-level itself has permission (so header checkbox initial state)
            $topHasPerm = !empty($List->getUserSingleMenus($this->id, $menu['id']));
            $topChecked = $topHasPerm ? 'checked' : '';

            echo '<div class="card card-info" id="menu-card-' . htmlspecialchars($menu['id']) . '">';
            echo '<div class="card-header d-flex justify-content-between align-items-center">';
            echo '<h3 class="card-title mb-0">' . htmlspecialchars($menu['menu_name']) . '</h3>';

            if (in_array($menu['menu_name'], $alwaysVisible, true)) {
                echo '<span class="badge badge-success">Always Visible</span>';
            } else {
                // header checkbox toggles all children in this card
                echo '<div class="icheck-primary d-inline">';
                echo '<input type="checkbox"
                           id="MAINMENUtodoCheck' . htmlspecialchars($menu['id']) . '"
                           class="mainmenu-checkbox"
                           data-cascade="1"
                           data-scope="menu-card-' . htmlspecialchars($menu['id']) . '"
                           onclick="PermissionCheckUncheck(\'' . htmlspecialchars($this->id) . '\', \'' . htmlspecialchars($menu['id']) . '\', \'MAINMENU\')"
                           ' . $topChecked . '>';
                echo '<label for="MAINMENUtodoCheck' . htmlspecialchars($menu['id']) . '"> Visible</label>';
                echo '</div>';
            }

            echo '</div>'; // card-header

            echo '<div class="card-body p-0">';
            echo '<table class="table">';
            echo '<thead><tr><th>Menu Name</th><th>Visible</th></tr></thead>';
            echo '<tbody>';

            $subMenus = $List->MenuByParent($menu['id']);
            // render direct children (and recursion will render deeper levels)
            renderMenuTable($subMenus, $List, $this->id, 1, $alwaysVisible);

            echo '</tbody>';
            echo '</table>';
            echo '</div>'; // card-body
            echo '</div>'; // card
        }
        ?>

         <?php
        // output buffer (optional) - content already echoed
        echo ob_get_clean();
    } // end SetupForm
} // end class


