(function () {
    "use strict";

    const cfg = window.MSAdminRoleWarning;
    if (!cfg) return;

    const { ajaxUrl, nonce, threshold, phrase } = cfg;
    const { __, _n, sprintf } = wp.i18n;

    // =========================================================
    //  Modal
    // =========================================================
    function showModal({ adminCount }) {
        return new Promise((resolve) => {
            const overlay = document.createElement("div");
            overlay.style.position = "fixed";
            overlay.style.inset = "0";
            overlay.style.background = "rgba(0,0,0,0.55)";
            overlay.style.zIndex = "100000";
            overlay.style.display = "flex";
            overlay.style.alignItems = "center";
            overlay.style.justifyContent = "center";
            overlay.style.padding = "24px";

            const modal = document.createElement("div");
            modal.setAttribute("role", "dialog");
            modal.setAttribute("aria-modal", "true");
            modal.style.width = "min(760px, 100%)";
            modal.style.maxHeight = "80vh";
            modal.style.overflow = "auto";
            modal.style.background = "#fff";
            modal.style.borderRadius = "12px";
            modal.style.boxShadow = "0 10px 30px rgba(0,0,0,0.35)";
            modal.style.padding = "20px";

            const title = document.createElement("h2");
            title.textContent = __(
                "Too many administrators",
                "rrze-settings",
            );

            const lead = document.createElement("p");
            lead.textContent = sprintf(
                _n(
                    "This site already has %d user with the Administrator role.",
                    "This site already has %d users with the Administrator role.",
                    adminCount,
                    "rrze-settings",
                ),
                adminCount,
            );

            const body = document.createElement("pre");
            body.textContent = __(
                "Having too many administrators can cause:\n" +
                    "• Risk of accidental configuration changes\n" +
                    "• Reduced traceability and accountability\n" +
                    "• Larger attack surface if an account is compromised\n" +
                    "• Governance and support complexity\n\n" +
                    "If you still wish to proceed, type EXACTLY the confirmation phrase below:",
                "rrze-settings",
            );
            body.style.whiteSpace = "pre-wrap";
            body.style.background = "#f6f7f7";
            body.style.padding = "12px";
            body.style.borderRadius = "8px";
            body.style.border = "1px solid #dcdcde";

            const label = document.createElement("label");
            label.textContent = __(
                "Confirmation phrase",
                "rrze-settings",
            );
            label.style.display = "block";
            label.style.fontWeight = "600";
            label.style.margin = "12px 0 6px 0";

            const phraseBox = document.createElement("div");
            phraseBox.textContent = phrase;
            phraseBox.style.padding = "10px 12px";
            phraseBox.style.border = "1px solid #dcdcde";
            phraseBox.style.borderRadius = "8px";
            phraseBox.style.background = "#fff";
            phraseBox.style.fontFamily =
                "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace";
            phraseBox.style.marginBottom = "10px";
            phraseBox.style.userSelect = "all";

            const input = document.createElement("input");
            input.type = "text";
            input.style.width = "100%";
            input.style.padding = "10px 12px";
            input.style.borderRadius = "8px";
            input.style.border = "1px solid #c3c4c7";

            const hint = document.createElement("div");
            hint.textContent = __(
                "You must type the phrase exactly as shown (case-sensitive).",
                "rrze-settings",
            );
            hint.style.fontSize = "12px";
            hint.style.color = "#50575e";
            hint.style.marginTop = "6px";

            const error = document.createElement("div");
            error.textContent = __(
                "The phrase does not match.",
                "rrze-settings",
            );
            error.style.display = "none";
            error.style.color = "#b32d2e";
            error.style.marginTop = "8px";
            error.style.fontWeight = "600";

            const actions = document.createElement("div");
            actions.style.display = "flex";
            actions.style.gap = "10px";
            actions.style.justifyContent = "flex-end";
            actions.style.marginTop = "16px";

            const cancelBtn = document.createElement("button");
            cancelBtn.type = "button";
            cancelBtn.className = "button";
            cancelBtn.textContent = __("Cancel", "rrze-settings");

            const confirmBtn = document.createElement("button");
            confirmBtn.type = "button";
            confirmBtn.className = "button button-primary";
            confirmBtn.textContent = __(
                "Proceed anyway",
                "rrze-settings",
            );

            actions.appendChild(cancelBtn);
            actions.appendChild(confirmBtn);

            modal.appendChild(title);
            modal.appendChild(lead);
            modal.appendChild(body);
            modal.appendChild(label);
            modal.appendChild(phraseBox);
            modal.appendChild(input);
            modal.appendChild(hint);
            modal.appendChild(error);
            modal.appendChild(actions);

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            function close(val) {
                overlay.remove();
                resolve(val);
            }

            cancelBtn.addEventListener("click", () => close(false));

            confirmBtn.addEventListener("click", () => {
                if (input.value.trim() === phrase) {
                    close(true);
                } else {
                    error.style.display = "block";
                    input.focus();
                }
            });

            setTimeout(() => input.focus(), 0);
        });
    }

    // =========================================================
    //  AJAX Accurate Check
    // =========================================================
    async function checkNeedWarning({ role, userId, userIds }) {
        const fd = new FormData();
        fd.append("action", "ms_admin_role_warning_check");
        fd.append("nonce", nonce);
        fd.append("role", role);

        if (userId) fd.append("user_id", String(userId));
        if (Array.isArray(userIds)) {
            userIds.forEach((id) => fd.append("user_ids[]", String(id)));
        }

        const res = await fetch(ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            body: fd,
        });

        const json = await res.json();
        if (!json || !json.success) {
            return { needWarning: false, adminCount: 0 };
        }

        return json.data;
    }

    // =========================================================
    //  A) Single User Screen
    // =========================================================
    (function bindSingleUserScreens() {
        const roleSelect =
            document.querySelector("select#role") ||
            document.querySelector('select[name="role"]');

        if (!roleSelect) return;

        const form = roleSelect.closest("form");
        if (!form) return;

        const userIdInput =
            form.querySelector('input[name="user_id"]') ||
            document.querySelector("input#user_id");

        const userId = userIdInput ? parseInt(userIdInput.value, 10) : null;

        const initialRole = String(roleSelect.value || "").toLowerCase();
        let lastNonAdminRole = roleSelect.value;
        let confirmedForAttempt = false;

        function isAdminRole(val) {
            return String(val).toLowerCase() === "administrator";
        }

        roleSelect.addEventListener("change", async () => {
            const current = String(roleSelect.value || "").toLowerCase();

            if (!isAdminRole(current)) {
                lastNonAdminRole = roleSelect.value;
                confirmedForAttempt = false;
                return;
            }

            if (initialRole === "administrator") return;
            if (confirmedForAttempt) return;

            const data = await checkNeedWarning({
                role: "administrator",
                userId,
            });

            if (!data.needWarning) return;

            const ok = await showModal(data);

            if (ok) {
                confirmedForAttempt = true;
            } else {
                confirmedForAttempt = false;
                roleSelect.value = lastNonAdminRole;
            }
        });

        form.addEventListener("submit", async (e) => {
            const current = String(roleSelect.value || "").toLowerCase();
            if (!isAdminRole(current)) return;
            if (initialRole === "administrator") return;
            if (confirmedForAttempt) return;

            e.preventDefault();

            const data = await checkNeedWarning({
                role: "administrator",
                userId,
            });

            if (!data.needWarning) {
                confirmedForAttempt = true;
                form.submit();
                return;
            }

            const ok = await showModal(data);
            if (ok) {
                confirmedForAttempt = true;
                form.submit();
            }
        });
    })();

    // =========================================================
    //  B) Bulk Users Screen
    // =========================================================
    (function bindBulkUsersScreen() {
        const usersForm =
            document.querySelector("form#posts-filter") ||
            document.querySelector("form#users-filter");

        if (!usersForm) return;

        const actionTop = usersForm.querySelector('select[name="action"]');
        const actionBottom = usersForm.querySelector('select[name="action2"]');
        const newRoleTop = usersForm.querySelector('select[name="new_role"]');
        const newRoleBottom = usersForm.querySelector(
            'select[name="new_role2"]',
        );

        let confirmedForAttempt = false;

        function getCheckedUserIds() {
            return Array.from(
                usersForm.querySelectorAll('input[name="users[]"]:checked'),
            )
                .map((el) => parseInt(el.value, 10))
                .filter((n) => Number.isFinite(n) && n > 0);
        }

        function pickBulkIntent() {
            if (actionTop?.value === "promote" && newRoleTop) {
                return { role: newRoleTop.value };
            }
            if (actionBottom?.value === "promote" && newRoleBottom) {
                return { role: newRoleBottom.value };
            }
            return { role: "" };
        }

        usersForm.addEventListener("submit", async (e) => {
            const { role } = pickBulkIntent();
            if (String(role).toLowerCase() !== "administrator") return;
            if (confirmedForAttempt) return;

            const userIds = getCheckedUserIds();
            if (!userIds.length) return;

            e.preventDefault();

            const data = await checkNeedWarning({
                role: "administrator",
                userIds,
            });

            if (!data.needWarning) {
                confirmedForAttempt = true;
                usersForm.submit();
                return;
            }

            const ok = await showModal(data);
            if (ok) {
                confirmedForAttempt = true;
                usersForm.submit();
            }
        });
    })();
})();
