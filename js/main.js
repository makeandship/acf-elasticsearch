jQuery(document).ready(function ($) {
  // endpoint URLs
  var CREATE_MAPPINGS_URI = "";
  var INDEX_POSTS_URI = "";
  var CLEAR_INDEX_URI = "";

  $("#create-mappings").click(function (e) {
    $("#mapping-spinner").addClass("is-active");
    $("#mapping-messages").html("Creating mappings ...");
    $("#create-mappings").attr("disabled", true);

    var data = {
      action: "create_mappings",
    };
    adminAjax(
      data,
      function () {
        $("#mapping-spinner").removeClass("is-active");
        $("#mapping-messages").html("Mappings created successfully");
        $("#create-mappings").attr("disabled", false);
        console.log("success");
      },
      function () {
        $("#mapping-spinner").removeClass("is-active");
        $("#mapping-messages").html("Mappings generation failed");
        $("#create-mappings").attr("disabled", false);
        console.log("error");
      }
    );
    return false;
  });

  $("#resume-indexing-posts").click(function (e) {
    $("#indexing-spinner").addClass("is-active");
    $("#indexing-messages").html("Indexing posts ...");
    $("#resume-indexing-posts").attr("disabled", true);

    indexPosts(false);

    return false;
  });

  $("#index-posts").click(function (e) {
    $("#indexing-spinner").addClass("is-active");
    $("#indexing-messages").html("Indexing posts ...");
    $("#index-posts").attr("disabled", true);

    indexPosts(true);

    return false;
  });

  $("#index-taxonomies").click(function (e) {
    $("#indexing-spinner").addClass("is-active");
    $("#indexing-messages").html("Indexing taxonomies ...");
    $("#index-taxonomies").attr("disabled", true);
    var data = {
      action: "index_taxonomies",
    };
    adminAjax(
      data,
      function (response) {
        $("#indexing-spinner").removeClass("is-active");
        $("#indexing-messages").html("Taxonomies indexed successfully");
        $("#index-taxonomies").attr("disabled", false);
      },
      function () {
        $("#indexing-spinner").removeClass("is-active");
        $("#indexing-messages").html("Error indexing taxonomies");
        $("#index-taxonomies").attr("disabled", false);
      }
    );
    return false;
  });

  $("#clear-index").click(function (e) {
    $("#indexing-spinner").addClass("is-active");
    $("#indexing-messages").html("Clearing index ...");
    $("#clear-index").attr("disabled", true);
    var data = {
      action: "clear_index",
    };
    adminAjax(
      data,
      function (response) {
        $("#indexing-spinner").removeClass("is-active");
        $("#indexing-messages").html("Index cleared successfully");
        $("#clear-index").attr("disabled", false);
      },
      function () {
        $("#indexing-spinner").removeClass("is-active");
        $("#indexing-messages").html("Error clearing index cleared");
        $("#clear-index").attr("disabled", false);
        d;
      }
    );
    return false;
  });

  function indexPosts(fresh) {
    var data = {
      action: "index_posts",
    };

    if (fresh) {
      data.fresh = true;
    }

    adminAjax(
      data,
      function (response) {
        var status = response.status;
        if (status) {
          if (status.page) {
            $("#acf-elasticsearch-page").html(status.page);
          }
          if (status.count) {
            if (status.count > status.total) {
              $("#acf-elasticsearch-count").html(status.total);
            } else {
              $("#acf-elasticsearch-count").html(status.count);
            }
          }
          if (status.total) {
            $("#acf-elasticsearch-total").html(status.total);
          }
          if (typeof status.completed !== "undefined") {
            $("#acf-elasticsearch-completed").html(
              status.completed ? "Yes" : "No"
            );
          }
          if (!status.completed) {
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
      function () {
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
      dataType: "json",
    });
  }
});
