import { addFilter } from "@wordpress/hooks";

// Get options from localized script
const { blocksToHide } = blockEditorLocalize;

const hideEmbedVariations = (settings, blockName) => {
    // Check if the block has variations
    if (settings.variations) {
        // Determine which variations to hide
        const hideAllVariations = blocksToHide.some(rule => {
            const [ruleBlockName, ruleVariation] = rule.split(':');
            return ruleBlockName === blockName && ruleVariation === '*';
        });

        // Filter variations based on all the hide rules
        settings.variations = settings.variations.filter((variation) => {
            const variationKey = `${blockName}:${variation.name}`;
            
            // Check if this variation or all variations should be hidden
            const shouldHide = blocksToHide.some(rule => {
                const [ruleBlockName, ruleVariation] = rule.split(':');
                return (
                    (ruleBlockName === blockName && ruleVariation === '*') ||  // Hide all variations
                    (ruleBlockName === blockName && ruleVariation === variation.name) // Hide specific variation
                );
            });

            return !shouldHide;
        });

        // If wildcard rule applies, hide all variations
        if (hideAllVariations) {
            settings.variations = [];
        }
    }

    return settings;
};

// Apply the filter to modify the block type settings
addFilter(
    "blocks.registerBlockType", // Hook to modify block settings
    "rrze-settings/hide-embed-variations", // Namespace for the filter
    hideEmbedVariations // Callback function
);
