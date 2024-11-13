/**
 * This JavaScript code implements a comprehensive search and filtering functionality for a webpage.
 * Key Features:
 * - `swiftypeSearch()`: Sends an AJAX GET request to fetch filtered search results and updates the URL dynamically without refreshing the page.
 * - `searchGetParametes()`: Extracts search parameters from the current URL.
 * - `changeURLWorldwide()`: Adjusts the `href` attributes of language switcher links to maintain search terms when switching languages.
 * - Event listeners handle:
 *    - Form submissions for search queries.
 *    - Category, year, language, and date-based filtering.
 *    - Autocomplete functionality with a debounce function to optimize performance.
 * - Includes user interface adjustments such as showing/hiding sub-filters and updating text elements.
 * 
 * Dependencies:
 * - jQuery library for event handling and AJAX calls.
 * 
 */

function swiftypeSearch(filterString, postFilter, subPostFilter, sortYear, sortDate, langString) {
    setSpinnerLoad();
  
    $.ajax({
      type: 'GET',
      url: WpUtils.ajaxurl,
      data: {
        action: 'SearchGeneralWithFiltersAjax',
        filter: filterString,
        post_filter: postFilter,
        sub_post_filter: subPostFilter,
        sort_lang: langString,
        sort_filter: sortDate,
        sort_year: sortYear
      },
      success: function(result) {
        var url = new URLSearchParams(window.location.search);
        var newPath = '';
  
        url.set("s", filterString);
        url.set("filter", postFilter);
        url.set("lang", langString);
        url.delete("page");
  
        if (subPostFilter) {
          url.set("subFilter", subPostFilter);
        } else {
          url.delete("subFilter"); 
        }
  
        if (!sortDate) {
          url.delete("sortBy");
          $('.sort_direction').empty();
        } else {
          url.set("sortBy", sortDate);
          $('.sort_direction').text(sortDate);
        }
  
        if (!sortYear || sortYear == 'all') {
          url.delete("sortYear");
          $('.sort_year').empty();
        } else {
          url.set("sortYear", sortYear);
          $('.sort_year').text(sortYear);
        }
  
        if (langString === "all" || langString === null) {
          $('.sort_lang').text('Global');
          $('.section-general-search-filters-dropdown input[type="checkbox"').prop('checked',false);
        } else {
          $('.sort_lang').text(langString);
        };
  
        if (langString === "it" || langString === "fr" || langString === "de") {
          newPath = "/" + langString + "/?" + url.toString();
        } else {
          newPath = "/?" +url.toString();
        }
  
  
        window.history.pushState(null, null, newPath);
  
        $('.section-general-search-results').html(result);
  
        removeSpinnerLoad();
      },
      error: function(error) {
        console.log("Please try again: "+ error)
      }
    });
  };
  
  function searchGetParametes() {
    var url = new URLSearchParams(window.location.search);
  
    return {
      search: $('.header-general-search').val(),
      filter: url.get('filter'),
      subFilter: url.get('subFilter'),
      sortYear: url.get('sortYear'),
      sortDate: url.get('sortBy'),
      langString: url.get('lang'),
      page: url.get('page'),
  
    }
  };
  
  
  function changeURLWorldwide() {
    var urlQuery = new URLSearchParams(window.location.search);
  
    if (urlQuery && urlQuery.has("lang")) {
      var langToChange;
      $(".sub-menu .lang-item a").each((i, e) => {
        switch (e.getAttribute("lang")) {
          case "de-DE": langToChange = "de"
            urlQuery.set("lang", langToChange)
            break;
  
          case "fr-FR": langToChange = "fr"
            urlQuery.set("lang", langToChange)
            break;
  
          case "it-IT": langToChange = "it"
            urlQuery.set("lang", langToChange)
            break;
  
          default: langToChange = "en"
            urlQuery.set("lang", langToChange)
            break;
        }
  
        $(e).attr("href", location.protocol + "//" + location.host + "/" + langToChange + "/?" + urlQuery.toString())
  
  
      });
      console.log("The links in worldwide icon has been change with lang parameter");
    }
  }
  
  $(document).ready(function() {
    // Change href in worldwide icon in search page to continuo with the lang and search terms when changed the lang with the polylang switch
    changeURLWorldwide()
    // End Change href in worldwide icon in search page
  
    //Filter by Input Search HomePage
    $('#search-general-form').on('submit', function(e) {
      e.preventDefault();
      var parameters = searchGetParametes();
  
      swiftypeSearch(parameters.search, parameters.filter, parameters.subFilter, parameters.sortYear, parameters.sortDate, parameters.langString);
    });
    //End Filter by Input Search HomePage
  
    //Filter by Input Search Result Page
    $('#search-general-form.js-results').on('submit', function(e) {
      e.preventDefault();
      var parameters = searchGetParametes();
  
      swiftypeSearch(parameters.search, parameters.filter, parameters.subFilter, parameters.sortYear, parameters.sortDate, parameters.langString);
    });
    //End Filter by Input Search Result Page
  
    //Filter by Category
  
    $(document).on('click', '.section-general-search-element-content-category', function() {
      $('.section-general-search-filters-items:not(.sub-list)').find('li[data-post="'+$(this).data('post') +'"]').trigger('click');
    });
  
    $('.section-general-search-filters-items > li').on('click', function() {
      var parameters = searchGetParametes();
      parameters.filter  = $(this).data('post');
      parameters.subFilter = $(this).data('subpost');
  
      $('.section-general-search-filters-items > li').removeClass('active');
      $(this).addClass('active');
  
      $('.section-general-search-sub-filter-media').hide();
      $('.section-general-search-sub-filter-insights').hide();
      $('.section-general-search-sub-filter-events').hide();
      $('.sub-media').removeClass('active');
      $('.sub-insights').removeClass('active');
      $('.sub-events').removeClass('active');
  
      if (parameters.filter == 'media') {
        $('.section-general-search-sub-filter-media').show();
        $('.sub-media').addClass('active');
      } else if (parameters.filter == 'insights') {
        $('.section-general-search-sub-filter-insights').show();
        $('.sub-insights').addClass('active');
      } else if (parameters.filter == 'events') {
        $('.section-general-search-sub-filter-events').show();
        $('.sub-events').addClass('active');
      } else {
        $('.section-general-search-sub-filter-media').hide();
        $('.section-general-search-sub-filter-insights').hide();
        $('.section-general-search-sub-filter-events').hide();
      };
  
      swiftypeSearch(parameters.search, parameters.filter, parameters.subFilter, parameters.sortYear, parameters.sortDate, parameters.langString);
  
       $('html, body').animate({
          scrollTop: $(".header-section-hero-search-page").offset().top - 70
      }, 1000);
    });
    //End Filter by Category
  
    //Filter by Year
    $('.dropdown-item-year').on('click', function() {
      var parameters = searchGetParametes();
      parameters.sortYear = $(this).data('year');
  
      swiftypeSearch(parameters.search, parameters.filter, parameters.subFilter, parameters.sortYear, parameters.sortDate, parameters.langString);
    });
    //End Filter by Year
  
    //Sort by Lang
    $('.dropdown-item-lang input').on('change', function() {
      var parameters = searchGetParametes();
      parameters.sortYear = $(this).data('year');
      var langArray = [];
  
      $('.section-general-search-filters-dropdown input[type="checkbox"]:checked').each(function() {
        langArray.push($(this).val());
      });
  
      parameters.langString = langArray.length > 0 ? langArray.join('-') : 'all';
  
      swiftypeSearch(parameters.search, parameters.filter, parameters.subFilter, parameters.sortYear, parameters.sortDate, parameters.langString);
    });
    //Global Option
    $('.dropdown-item-lang.js-global').on('click', function() {
      var parameters = searchGetParametes();
  
      swiftypeSearch(parameters.search, parameters.filter, parameters.subFilter, parameters.sortYear, parameters.sortDate, 'all');
    });
    //End Global Option
    //End Sort by Lang
  
    //Sort by Date
    $('.dropdown-item-direction').on('click', function() {    
      if($(this).data('sort') == 'default') {
        var parameters = searchGetParametes();
        parameters.sortDate = '';
      } else{
        var parameters = searchGetParametes();
        parameters.sortDate = $(this).data('sort');  
      }
      
      swiftypeSearch(parameters.search, parameters.filter, parameters.subFilter, parameters.sortYear, parameters.sortDate, parameters.langString);
    });
    //End Sort by Date
  
    //Autocomplete
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };
      
    $('.swifty-form-search-input').keyup(debounce(function(e) {
      var postFilter = $(this).data('category');
      var string = $('.swifty-form-search-input').val();
      var lang = $('.swifty-form-lang-select').val();
      $('.lds-dual-ring').addClass('active');
      if (string.length == 0) {
        $('.swifty-form-search-autocomplete-results').hide();
        $('.lds-dual-ring').removeClass('active');
      } else {
        $.ajax({
          type: 'GET',
          url: WpUtils.ajaxurl,
          data: {
            action: 'SearchAutocomplete',
            string: string,
            postFilter: postFilter,
            sort_lang: lang
          },
          success: function(result) {
            $('.swifty-form-search-autocomplete-results').show();
            $('.swifty-form-search-autocomplete-results').html(result);
            $('.lds-dual-ring').removeClass('active');
          },
          error: function(e) {
            console.log(e);
          }
        });
      }
    }, 500));
    //End Autocomplete
  
    if (window.location.search.indexOf('media') > 1) {
      $('.section-general-search-sub-filter-media').show();
    } else if (window.location.search.indexOf('insights') > 1) {
      $('.section-general-search-sub-filter-insights').show();
    } else if (window.location.search.indexOf('events') > 1) {
      //$('.sub-events').addClass('active');
      $('.section-general-search-sub-filter-events').show();
    } else {
      $('.section-general-search-sub-filter-media').hide();
      $('.section-general-search-sub-filter-insights').hide();
      $('.section-general-search-sub-filter-events').hide();
    }
  
  });
  