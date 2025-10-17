import "./attachment-media-filters.scss";

(function ($, _, wp) {
    "use strict";
    if (!wp || !wp.media) return;

    var media = wp.media;

    // ---- Data from PHP (localized) ----
    var docData = window.RRZE_AttachmentDocument || null;
    var catData = window.RRZE_AttachmentCategory || null;
    var tagData = window.RRZE_AttachmentTag || null;

    // Helper: true if dataset exists and has at least one term
    function hasTerms(data) {
        return !!(data && Array.isArray(data.terms) && data.terms.length > 0);
    }

    // ----- Attachment Document (hierarchical) -----
    var AttachmentDocumentFilter = media.view.AttachmentFilters.extend({
        id: "rrze-attachment-document-filter",
        className: "attachment-filters rrze-attachment-document-filter",
        createFilters: function () {
            var filters = {};

            filters.all = {
                text:
                    docData && docData.i18n && docData.i18n.all
                        ? docData.i18n.all
                        : "All",
                props: { attachment_document: "" },
                priority: 10,
            };

            (docData.terms || []).forEach(function (term) {
                var indent = "";
                for (var i = 0; i < (term.depth || 0); i++) indent += "— ";
                filters["doc_" + term.slug] = {
                    text:
                        indent +
                        term.name +
                        (typeof term.count !== "undefined"
                            ? " (" + term.count + ")"
                            : ""),
                    props: { attachment_document: term.slug },
                    priority: 20 + (term.depth || 0),
                };
            });

            this.filters = filters;
        },
    });

    // ----- Attachment Category (hierarchical) -----
    var AttachmentCategoryFilter = media.view.AttachmentFilters.extend({
        id: "rrze-attachment-category-filter",
        className: "attachment-filters rrze-attachment-category-filter",
        createFilters: function () {
            var filters = {};

            filters.all = {
                text:
                    catData && catData.i18n && catData.i18n.all
                        ? catData.i18n.all
                        : "All",
                props: { attachment_category: "" },
                priority: 10,
            };

            (catData.terms || []).forEach(function (term) {
                var indent = "";
                for (var i = 0; i < (term.depth || 0); i++) indent += "— ";
                filters["cat_" + term.slug] = {
                    text:
                        indent +
                        term.name +
                        (typeof term.count !== "undefined"
                            ? " (" + term.count + ")"
                            : ""),
                    props: { attachment_category: term.slug },
                    priority: 20 + (term.depth || 0),
                };
            });

            this.filters = filters;
        },
    });

    // ----- Attachment Tag (non-hierarchical) -----
    var AttachmentTagFilter = media.view.AttachmentFilters.extend({
        id: "rrze-attachment-tag-filter",
        className: "attachment-filters rrze-attachment-tag-filter",
        createFilters: function () {
            var filters = {};

            filters.all = {
                text:
                    tagData && tagData.i18n && tagData.i18n.all
                        ? tagData.i18n.all
                        : "All",
                props: { attachment_tag: "" },
                priority: 10,
            };

            (tagData.terms || []).forEach(function (term) {
                filters["tag_" + term.slug] = {
                    text:
                        term.name +
                        (typeof term.count !== "undefined"
                            ? " (" + term.count + ")"
                            : ""),
                    props: { attachment_tag: term.slug },
                    priority: 20,
                };
            });

            this.filters = filters;
        },
    });

    // Utility: insert $el right after $anchor and return $el (as new anchor)
    function insertAfter($anchor, $el) {
        if ($anchor && $anchor.length) $el.insertAfter($anchor);
        return $el;
    }

    // Extend AttachmentsBrowser to inject and position filters
    var OrigAttachmentsBrowser = media.view.AttachmentsBrowser;
    media.view.AttachmentsBrowser = OrigAttachmentsBrowser.extend({
        createToolbar: function () {
            // Build the default toolbar first
            OrigAttachmentsBrowser.prototype.createToolbar.apply(
                this,
                arguments
            );

            var $secondary = this.toolbar.$(".media-toolbar-secondary");
            if (!$secondary.length) return;

            // Prevent duplicates (DOM check)
            if (
                $secondary.find(
                    "#rrze-attachment-document-filter, #rrze-attachment-category-filter, #rrze-attachment-tag-filter"
                ).length
            ) {
                return;
            }

            // Build views ONLY if dataset exists AND has terms
            var docView = hasTerms(docData)
                ? new AttachmentDocumentFilter({
                      controller: this.controller,
                      model: this.collection.props,
                  }).render()
                : null;

            var catView = hasTerms(catData)
                ? new AttachmentCategoryFilter({
                      controller: this.controller,
                      model: this.collection.props,
                  }).render()
                : null;

            var tagView = hasTerms(tagData)
                ? new AttachmentTagFilter({
                      controller: this.controller,
                      model: this.collection.props,
                  }).render()
                : null;

            // If none has terms, do nothing
            if (!docView && !catView && !tagView) return;

            // Add to secondary region so WP manages lifecycles
            if (docView)
                this.toolbar.views.add(".media-toolbar-secondary", docView);
            if (catView)
                this.toolbar.views.add(".media-toolbar-secondary", catView);
            if (tagView)
                this.toolbar.views.add(".media-toolbar-secondary", tagView);

            // Place AFTER the built-in date select, keeping order: Document → Category → Tag
            var $date = $secondary.find("#media-attachment-date-filters");
            var anchor = $date.length ? $date : $secondary.children().last();

            if (docView) anchor = insertAfter(anchor, $(docView.el));
            if (catView) anchor = insertAfter(anchor, $(catView.el));
            if (tagView) anchor = insertAfter(anchor, $(tagView.el));
        },
    });
})(jQuery, _, wp);
