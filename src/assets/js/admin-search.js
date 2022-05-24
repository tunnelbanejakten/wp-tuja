var tujaSearch = (function () {
  var debounce = function (callback, delay) {
    let timer = null
    return function () {
      var args = arguments
      var that = this
      if (timer) {
        clearTimeout(timer)
      }
      timer = setTimeout(function () {
        callback.apply(that, args)
      }, delay);
    }
  }
  return {
    init: function ($) {
      var $input = $('#search-input-field')
      var $resultPendingContainer = $('#search-result-pending')
      var $resultContainer = $('#search-result-container')
      var groupPageUrlPattern = $input.data('groupPageUrlPattern')
      var lastSearchQuery = null
      var search = function (event) {
        var searchQuery = this.value
        if (searchQuery === lastSearchQuery || !searchQuery) {
          return
        }
        var url = $input.data('queryEndpoint').replace('QUERY', encodeURIComponent(searchQuery))
        $resultPendingContainer.show()
        $resultContainer.hide()
        $.ajax(url)
          .done(function (data) {
            var $people = data.people.map(function (person) {
              var $person = $('<tr/>')
              $person.append($('<td/>')
                .text(person.name))
              $person.append($('<td/>')
                .append($('<a/>')
                  .attr('href', groupPageUrlPattern.replace('GROUPID', person.group_id))
                  .text('Grupp')))

              if (person.email) {
                $person.append($('<td/>')
                  .append($('<a/>')
                    .attr('href', `mailto:${person.email}`)
                    .text(person.email)))
              }
              if (person.phone) {
                $person.append($('<td/>')
                  .append($('<a/>')
                    .attr('href', `tel:${person.phone}`)
                    .text(person.phone)))
              }
              return $person
            })
            $('#search-result-people-container').toggle(data.people.length > 0)
            $('#search-result-people').empty().append($people)

            var $groups = data.groups.map(function (group) {
              return $('<tr/>')
                .append($('<td/>')
                  .append($('<a/>')
                    .attr('href', groupPageUrlPattern.replace('GROUPID', group.id))
                    .text(group.name)))
            })
            $('#search-result-groups-container').toggle(data.groups.length > 0)
            $('#search-result-empty').toggle(data.groups.length === 0 && data.people.length === 0)
            $('#search-result-groups').empty().append($groups)

            $resultPendingContainer.hide()
            $resultContainer.show()
            lastSearchQuery = searchQuery
          })
      }
      var searchFunction = debounce(search, 500)
      $input
        .change(searchFunction)
        .keyup(searchFunction)
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaSearch.init($)
})
