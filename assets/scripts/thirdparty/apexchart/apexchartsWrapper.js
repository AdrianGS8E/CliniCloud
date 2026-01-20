/**
 * ApexCharts Wrapper for SmartAdmin
 * Prevents CSS injection by the library and ensures we only use our own CSS
 */

import OriginalApexCharts from './apexcharts.esm.js';

// Create a wrapper around ApexCharts
class SmartApexCharts extends OriginalApexCharts {
    // Icon mapping for toolbar
    static iconMap = {
        'apexcharts-zoomin-icon': 'plus-circle',
        'apexcharts-zoomout-icon': 'minus-circle',
        'apexcharts-zoom-icon': 'plus',
        'apexcharts-pan-icon': 'move',
        'apexcharts-reset-icon': 'refresh-ccw',
        'apexcharts-menu-icon': 'menu'
    };

    constructor(el, options) {
        // Ensure options object exists
        options = options || {};
        options.chart = options.chart || {};
        
        // Global styling defaults
        const defaults = {
            chart: {
                fontFamily: 'inherit',
                disableCssInjection: true
            },
            title: {
                style: {
                    color: window.colorMap.bootstrapVars.bodyColor.hex
                }
            },
            subtitle: {
                style: {
                    color: window.colorMap.bootstrapVars.bodyColor.rgba(0.5)
                }
            },
            grid: {
                borderColor: window.colorMap.bootstrapVars.bodyColor.rgba(0.2)
            },
            xaxis: {
                labels: {
                    style: {
                        colors: window.colorMap.bootstrapVars.bodyColor.hex
                    }
                },
                title: {
                    style: {
                        color: window.colorMap.bootstrapVars.bodyColor.hex
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: window.colorMap.bootstrapVars.bodyColor.hex
                    }
                },
                title: {
                    style: {
                        color: window.colorMap.bootstrapVars.bodyColor.hex
                    }
                }
            },
            tooltip: {
                theme: 'dark'
            },
            legend: {
                labels: {
                    colors: window.colorMap.bootstrapVars.bodyColor.hex
                }
            }
        };

        // Merge options with defaults
        options = SmartApexCharts.deepMerge(defaults, options);
        
        // Call parent constructor with merged options
        super(el, options);
        
        // Create a MutationObserver to remove any stray style tags and handle icons
        this._styleObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeName === 'STYLE' && 
                            node.id && 
                            node.id.startsWith('apexcharts')) {
                            node.remove();
                        }
                        // Handle toolbar icons
                        if (node.classList && node.classList.contains('apexcharts-toolbar')) {
                            this._replaceToolbarIcons(node);
                        }
                    });
                }
            });
        });
        
        // Start observing the document for added style nodes
        this._styleObserver.observe(document.head, {
            childList: true
        });

        // Also observe body for toolbar changes
        this._styleObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Replace toolbar icons with custom sprite system icons
    _replaceToolbarIcons(toolbar) {
        Object.entries(SmartApexCharts.iconMap).forEach(([className, iconName]) => {
            const iconContainer = toolbar.querySelector(`.${className}`);
            if (iconContainer) {
                // Add button classes
                iconContainer.classList.add('btn', 'btn-icon', 'btn-sm', 'btn-outline-default', 'p-0');
                // Clear existing icon
                iconContainer.innerHTML = '';
                // Create new icon using sprite system
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.classList.add('sa-icon', 'sa-thick', 'sa-nofill');
                const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
                use.setAttributeNS('http://www.w3.org/1999/xlink', 'href', `img/sprite.svg#${iconName}`);
                svg.appendChild(use);
                iconContainer.appendChild(svg);
            }
        });
    }
    
    // Clean up observer when chart is destroyed
    destroy() {
        if (this._styleObserver) {
            this._styleObserver.disconnect();
        }
        return super.destroy();
    }

    // Static deep merge helper
    static deepMerge(target, source) {
        const isObject = (obj) => obj && typeof obj === 'object' && !Array.isArray(obj);
        const output = Object.assign({}, target);
        
        if (isObject(target) && isObject(source)) {
            Object.keys(source).forEach(key => {
                if (isObject(source[key])) {
                    if (!(key in target)) {
                        Object.assign(output, { [key]: source[key] });
                    } else {
                        output[key] = SmartApexCharts.deepMerge(target[key], source[key]);
                    }
                } else {
                    Object.assign(output, { [key]: source[key] });
                }
            });
        }
        
        return output;
    }
}

// Override the prototype method that injects CSS
SmartApexCharts.prototype.injectCSS = function() {
    return;
};

// Export wrapped version
export default SmartApexCharts; 