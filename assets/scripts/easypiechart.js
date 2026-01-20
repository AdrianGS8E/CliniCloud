/**
 * Easy Pie Chart Implementation
 * Vanilla JavaScript implementation for easy pie charts
 */
import EasyPieChart from './thirdparty/easypiechart/easypiechart.es6.js';
document.addEventListener('DOMContentLoaded', function () {
  /* 	Easy pie chart initialization
  	Pure JavaScript implementation with no jQuery dependencies
   */
  document.querySelectorAll('.js-easy-pie-chart').forEach(function (element) {
    // Get element properties using vanilla JS
    const computedStyle = window.getComputedStyle(element);
    const barcolor = computedStyle.color || 'var(--primary-700)';

    // Check if window.colorMap exists, if not use a fallback
    let trackcolor;
    try {
      if (window.colorMap && window.colorMap.bootstrapVars && window.colorMap.bootstrapVars.bodyColorRgb) {
        trackcolor = window.colorMap.bootstrapVars.bodyColorRgb.rgba(0.07);
      } else {
        trackcolor = 'rgba(0,0,0,0.04)';
      }
    } catch (e) {
      trackcolor = 'rgba(0,0,0,0.04)';
    }

    // Read dataset attributes with fallbacks
    const size = parseInt(element.dataset.piesize) || 50;
    const scalecolor = element.dataset.scalecolor || computedStyle.color;
    const scalelength = parseInt(element.dataset.scalelength) || 0;
    const linewidth = parseInt(element.dataset.linewidth) || parseInt(size / 8.5);
    const linecap = element.dataset.linecap || 'butt'; // butt, round and square.

    // Create EasyPieChart instance
    const chart = new EasyPieChart(element, {
      size: size,
      barColor: barcolor,
      trackColor: trackcolor,
      scaleColor: scalecolor,
      scaleLength: scalelength,
      lineCap: linecap,
      lineWidth: linewidth,
      animate: {
        duration: 1500,
        enabled: true
      },
      easing: 'easeOutQuad',
      // Use our built-in easing function
      onStep: function (from, to, percent) {
        // Find the percentage element and update its text
        const percentElement = element.querySelector('.js-percent');
        if (percentElement) {
          percentElement.textContent = Math.round(percent);
        }
      }
    });
  });
});