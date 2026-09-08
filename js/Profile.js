 /**
         * Post a single permission change to the backend.
         * Returns Promise resolving to parsed JSON.
         */
        function postPermissionUpdate(employee_id, menuId, isChecked, permission_type) {
 

          return fetch('includes/UpdateMenuPermission.inc.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `employee_id=${encodeURIComponent(employee_id)}&menu_id=${encodeURIComponent(menuId)}&status=${encodeURIComponent(isChecked)}&permission_type=${encodeURIComponent(permission_type)}`
          }).then(r => r.json());
        }

        /**
         * Handles a click on any "MAINMENU" checkbox (both main header and submenus).
         * Signature preserved: PermissionCheckUncheck(employee_id, menuId, permission_type)
         *
         * Behavior:
         * - Update clicked menu permission via AJAX.
         * - If the clicked checkbox has data-cascade="1" (main header), cascade the same state to
         *   all submenu checkboxes inside the same card and send backend requests for each changed child.
         * - If clicked is a submenu (data-cascade="0"), recalculate parent header checked-state:
         *   parent becomes checked only if ALL children are checked. Keep parent backend state in sync.
         */
        async function PermissionCheckUncheck(employee_id, menuId, permission_type) {

          const checkboxId = permission_type + 'todoCheck' + menuId;
          const cb = document.getElementById(checkboxId);
          if (!cb) return;

          const isChecked = cb.checked ? 1 : 0;
          const isCascade = cb.dataset.cascade === '1';

          try {
            // 1) Update backend for this clicked menu
            const firstResp = await postPermissionUpdate(employee_id, menuId, isChecked, permission_type);

            // 2) If this is a main header (cascade), set all child checkboxes in the same card
            if (isCascade) {
              // find container by data-scope or nearest .card
              const scopeId = cb.dataset.scope || cb.closest('.card')?.id;
              const container = scopeId ? document.getElementById(scopeId) : cb.closest('.card');

              if (container) {
                const children = Array.from(container.querySelectorAll('tbody input[type="checkbox"][id^="MAINMENUtodoCheck"]'));

                // Prepare promises for only those children that change state
                const promises = [];

                children.forEach(child => {
                  // skip if same element (it might be included) or disabled
                  if (child === cb || child.disabled) return;
                  const desired = !!isChecked;
                  if (child.checked !== desired) {
                    child.checked = desired;
                    const childMenuId = child.id.replace('MAINMENUtodoCheck', '');
                    // Post the change
                    promises.push(postPermissionUpdate(employee_id, childMenuId, isChecked, permission_type));
                  }
                });

                if (promises.length) {
                  await Promise.all(promises);
                }
              }

              // single toast for cascade action
              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: firstResp.status || 'success',
                  title: firstResp.message || (isChecked ? 'Enabled menu visibility' : 'Disabled menu visibility'),
                  showConfirmButton: false,
                  timer: 1400
                });
              }
              return;
            }

            // 3) If a submenu was clicked: sync header (parent) checkbox
            const row = cb.closest('tr');
            const parentId = cb.dataset.parentId;
            if (parentId) {
              const parentCard = document.getElementById('menu-card-' + parentId) || cb.closest('.card');
              if (parentCard) {
                const childCbs = Array.from(parentCard.querySelectorAll('tbody input[type="checkbox"][id^="MAINMENUtodoCheck"]'));
                // Exclude checkboxes that are under different parents (if any)
                const relevant = childCbs.filter(x => (x.dataset.parentId == parentId));
                const allChecked = relevant.length ? relevant.every(x => x.checked || x.disabled) : false;

                const parentToggle = parentCard.querySelector('.card-header input[type="checkbox"][id^="MAINMENUtodoCheck"]');
                if (parentToggle && !parentToggle.disabled) {
                  // If parent state differs, update DOM and backend
                  if (parentToggle.checked !== allChecked) {
                    parentToggle.checked = allChecked;
                    const parentMenuId = parentToggle.id.replace('MAINMENUtodoCheck', '');
                    // keep backend parent in sync
                    await postPermissionUpdate(employee_id, parentMenuId, allChecked ? 1 : 0, permission_type);
                  }
                }
              }
            }

            // 4) Toast for the single submenu toggle
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: firstResp.status || 'success',
                title: firstResp.message || (isChecked ? 'Visible' : 'Hidden'),
                showConfirmButton: false,
                timer: 1100
              });
            }

          } catch (error) {
            console.error('Error:', error);
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Permission update failed',
                text: error?.message || 'Network or server error',
                showConfirmButton: true
              });
            }
          }
        }