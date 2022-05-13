var tujaStateGraph = (function () {
  return {
    init: function ($) {
      mermaid.initialize({
        startOnLoad: false,
        theme: 'neutral'
      })

      $('.tuja-stategraph').each(function () {
        var container = this
        var randomId = 'tuja-stategraph-' + Math.random().toString(36).substr(2, 9)
        mermaid.render(randomId, this.dataset.definition, function (svgGraph) {
          container.innerHTML = svgGraph
          var width = container.firstChild.width.baseVal.value
          container.firstChild.style.width = width * parseFloat(container.dataset.widthFactor)
        })
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaStateGraph.init($)
})

