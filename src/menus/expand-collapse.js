(function ($, l10n) {
    var rrzeMenus = {
        init: function () {
            this.settings.init();
            this.setUpToggleElements();
            $("#wpbody").on("sortstop", "#menu-to-edit", rrzeMenus.refresh);
        },
        settings: {
            menus: [],
            init: function () {
                var raw = getUserSetting("rrze_menus_collapsed").toString(),
                    x,
                    temp;
                if (/^\d+(s\d+)*/.test(raw)) {
                    raw = raw.split("s");
                    for (x in raw) {
                        temp = parseInt(raw[x]);
                        if (temp) {
                            this.menus.push(temp);
                        }
                    }
                }
            },
            find: function (id) {
                var x;
                for (x in this.menus) {
                    if (this.menus[x] === id) {
                        return x;
                    }
                }
                return undefined;
            },
            exists: function (id) {
                return !(this.find(id) === undefined);
            },
            add: function (id) {
                id = parseInt(id);
                if (!this.exists(id)) {
                    this.menus.push(id);
                }
                this.save();
            },
            remove: function (id) {
                id = parseInt(id);
                var key = this.find(id);
                if (key === undefined) {
                    return;
                }
                delete this.menus[key];
                this.save();
            },
            save: function () {
                var val = this.menus.join("s");
                setUserSetting("rrze_menus_collapsed", val);
            },
        },
        refresh: function () {
            setTimeout(function () {
                rrzeMenus.setUpToggleElements.call(rrzeMenus);
            }, 50);
        },
        setUpToggleElements: function () {
            $(".rrze-menus-toggle").each(function (key, item) {
                rrzeMenus.setUpToggleElement(item);
            });
        },
        setUpToggleElement: function (el) {
            el = $(el);
            if (!el.data("rrze-menus-setup")) {
                el.click(this.handleClick);
                if (this.settings.exists(this.getMenuItemId(el))) {
                    el.data("rrze-menus-collapsed", true);
                    this.getMenuItem(el).childMenuItems().hide();
                }
            }
            this.setText(el);
            this.setTitleAttr(el);
            if (this.getMenuItem(el).childMenuItems().length) {
                el.removeClass("hidden").addClass("rrze-menus-active");
            } else {
                el.removeClass("rrze-menus-active").addClass("hidden");
            }
            el.data("rrze-menus-setup", true);
        },
        isCollapsed: function (el) {
            return !!$(el).data("rrze-menus-collapsed");
        },
        getMenuItemId: function (el) {
            var id = this.getMenuItem(el).attr("id").replace("menu-item-", "");
            return parseInt(id);
        },
        setState: function (el) {
            el = $(el);
            var new_state = !$(el).data("rrze-menus-collapsed");
            if (new_state) {
                this.settings.add(this.getMenuItemId(el));
            } else {
                this.settings.remove(this.getMenuItemId(el));
            }
            el.data("rrze-menus-collapsed", new_state);
        },
        setText: function (el) {
            var classToUse = this.isCollapsed(el)
                ? "dashicons-arrow-down-alt2"
                : "dashicons-arrow-up-alt2";
            $(el)
                .text("")
                .append(
                    $("<span></span>")
                        .removeClass(
                            "dashicons dashicons-plus-alt dashicons-dismiss"
                        )
                        .addClass("dashicons")
                        .addClass(classToUse)
                );
        },
        setTitleAttr: function (el) {
            var textToUse = this.isCollapsed(el) ? l10n.expand : l10n.collapse;
            $(el).attr("title", textToUse);
        },
        getMenuItem: function (el) {
            return $(el).closest(".menu-item");
        },
        handleClick: function (event) {
            event.preventDefault();
            var t = this;
            var menuItem = rrzeMenus.getMenuItem(t);
            var children = menuItem.childMenuItems();
            if (!rrzeMenus.isCollapsed(t)) {
                children.slideUp("fast");
            } else {
                children.find(".rrze-menus-active").each(function (key, item) {
                    if (rrzeMenus.isCollapsed(item)) {
                        children = children.not(
                            rrzeMenus.getMenuItem(item).childMenuItems()
                        );
                    }
                });
                children.slideDown("fast");
            }
            rrzeMenus.setState(t);
            rrzeMenus.setText(t);
            rrzeMenus.setTitleAttr(t);
        },
    };
    $(document).ready(function () {
        rrzeMenus.init();
    });
})(jQuery, rrzeMenusL10n);
