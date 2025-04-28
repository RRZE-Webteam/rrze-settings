import { dispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

function initialize() {
    // ...

    dispatch( preferencesStore ).setDefaults(
        'namespace/editor-or-plugin-name',
        {
            myBooleanFeature: true,
        }
    );

    // ...
}