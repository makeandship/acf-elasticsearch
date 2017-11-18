jQuery(document).ready(function($) {
  // endpoint URLs
  var CREATE_MAPPINGS_URI = "";
  var INDEX_POSTS_URI = "";
  var CLEAR_INDEX_URI = "";

  $("#create-mappings").click(function(e) {
    $("#mapping-spinner").addClass("is-active");
    $("#mapping-messages").html("Creating mappings ...");
    $("#create-mappings").attr("disabled", true);

    var data = {
      action: "create_mappings"
    };
    adminAjax(
      data,
      function() {
        $("#mapping-spinner").removeClass("is-active");
        $("#mapping-messages").html("Mappings created successfully");
        $("#create-mappings").attr("disabled", false);
        console.log("success");
      },
      function() {
        $("#mapping-spinner").removeClass("is-active");
        $("#mapping-messages").html("Mappings generation failed");
        $("#create-mappings").attr("disabled", false);
        console.log("error");
      }
    );
    return false;
  });

  $("#resume-indexing-posts").click(function(e) {
    console.log("resume indexing posts");

    indexPosts(false);

    return false;
  });

  $("#index-posts").click(function(e) {
    $("#indexing-spinner").addClass("is-active");
    $("#indexing-messages").html("Indexing posts ...");
    $("#index-posts").attr("disabled", true);

    indexPosts(true);

    return false;
  });

  $("#index-taxonomies").click(function(e) {
    console.log("index taxonomies");
    var data = {
      action: "index_taxonomies"
    };
    adminAjax(
      data,
      function(response) {
        console.log("success");
      },
      function() {
        console.log("error");
      }
    );
    return false;
  });

  $("#clear-index").click(function(e) {
    console.log("clear index");
    var data = {
      action: "clear_index"
    };
    adminAjax(
      data,
      function(response) {
        console.log("success");
      },
      function() {
        console.log("error");
      }
    );
    return false;
  });

  function indexPosts(fresh) {
    var data = {
      action: "index_posts"
    };

    if (fresh) {
      data.fresh = true;
    }

    adminAjax(
      data,
      function(response) {
        var status = response.status;
        if (status) {
          var complete = true;

          if (status.count && status.page && status.total) {
            if (status.count < status.total) {
              complete = false;
            }
          } else {
            for (var blogId in status) {
              var site = status[blogId];
              if (site.count < site.total) {
                complete = false;
                break;
              }
            }
          }

          if (!complete) {
            console.log("More posts to index");
            $("#indexing-messages").html(
              `Indexed ${status.count} of ${status.total}`
            );
            indexPosts();
          } else {
            $("#indexing-messages").html(`All ${status.total} posts indexed`);
            $("#indexing-spinner").removeClass("is-active");
            $("#indexing-container input[type='submit']").attr(
              "disabled",
              false
            );
            console.log("All posts indexed");
          }
        }
      },
      function() {
        console.log("error");
      }
    );
  }

  function adminAjax(data, success, error) {
    var url = window.acfElasticsearchManager.ajaxUrl;
    $.ajax({
      url: url,
      type: "post",
      data: data,
      success: success,
      error: error,
      dataType: "json"
    });
  }
});
