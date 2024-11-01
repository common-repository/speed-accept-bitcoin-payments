const fields = [
  {
    fieldName: "speed_payment_method_name",
  },
  {
    fieldName: "speed_statement_descriptor",
  },
  {
    fieldName: "speed_description",
  },
];

const baseURL = "https://api.tryspeed.com/";
const appBaseURL = "https://appapi.tryspeed.com/";
const clientId = "lub1g664sB49G00t";
const speedAuthUrl = "https://app.tryspeed.com/authorize?";
const speedPluginRedirectUrl = $("#speed_plugin_redirect_url").val();
const webhookUrl = $("#speed_webhook_url").val();
const adminAjaxUrl = $("#admin-ajax-url").val();
const successIcon = $("#speed_success_img").val();

let speedCode = $("#speed_code_data").val();

function closeAlert(alertBox) {
  alertBox.remove();
}

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

getKeyDataFunction = async (speedCodeData) => {
  try {
    await delay(30 * 1000);
    const result = await $.ajax({
      url: appBaseURL + "manage-app/fetch-key",
      type: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      data: JSON.stringify({
        authentication_code: speedCodeData.speedCode,
        client_id: clientId,
      }),
    });

    return result;
  } catch (error) {
    throw error;
  }
};

const checkWebhookData = (data) => {
  for (let i = 0, len = data.length; i < len; i++) {
    const events = data[i].enabled_events;

    if (events.includes("checkout_session.paid")) {
      return data[i];
    }
  }

  return null;
};

const generateWebhookSecret = async (keys) => {
  try {
    const { test_restricted_key = "", live_restricted_key = "" } = keys;
    let testSecretKey, liveSecretKey;

    const testPromise = new Promise(async (resolve, reject) => {
      try {
        const res = await $.ajax({
          url: baseURL + "webhooks/filter",
          type: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Basic " + btoa(test_restricted_key),
          },
          data: JSON.stringify({
            limit: 100,
            status: ["active"],
            url: webhookUrl,
          }),
        });

        resolve(res);
      } catch (err) {
        reject(err);
      }
    });

    const livePromise = new Promise(async (resolve, reject) => {
      try {
        const res = await $.ajax({
          url: baseURL + "webhooks/filter",
          type: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Basic " + btoa(live_restricted_key),
          },
          data: JSON.stringify({
            limit: 100,
            status: ["active"],
            url: webhookUrl,
          }),
        });

        resolve(res);
      } catch (err) {
        reject(err);
      }
    });

    await Promise.allSettled([testPromise, livePromise]).then(async (res) => {
      const testRes = res[0];
      const liveRes = res[1];

      console.log(testRes, liveRes, "here");

      if (testRes.status === "rejected" || liveRes.status === "rejected") {
        throw err;
      }

      if (testRes.status === "fulfilled") {
        const testSecret = checkWebhookData(testRes.value.data);
        if (testSecret) testSecretKey = testSecret.secret;
      }

      if (liveRes.status === "fulfilled") {
        const liveSecret = checkWebhookData(liveRes.value.data);
        if (liveSecret) liveSecretKey = liveSecret.secret;
      }

      if (testSecretKey && liveSecretKey) {
        return;
      }

      if (!testSecretKey) {
        try {
          const webhook = await createWebhook(test_restricted_key);
          testSecretKey = webhook.secret;
        } catch (err) {
          throw err;
        }
      }

      if (!liveSecretKey) {
        try {
          const webhook = await createWebhook(live_restricted_key);
          liveSecretKey = webhook.secret;
        } catch (err) {
          throw err;
        }
      }
    });

    return { testSecretKey, liveSecretKey };
  } catch (error) {
    throw error;
  }
};

createWebhook = async (key) => {
  const webhookRes = await $.ajax({
    url: baseURL + "webhooks",
    type: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: "Basic " + btoa(key),
    },
    data: JSON.stringify({
      enabled_events: ["checkout_session.paid"],
      api_version: "2022-10-15",
      description: "This is an woocomerce payment webhook",
      url: webhookUrl,
    }),
  });

  return webhookRes;
};

$("#speed_save_btn").hide();
$(".loader-sec").hide();

if (speedCode === "") {
  $(".speed-steps").show();
  $("#setting-section").hide();
  $(".disconnect-btn").hide();
  $("#speed_save_btn").hide();
} else if (speedCode !== "" && $("#speed_test_restricted_key").val() === "") {
  $(".speed-steps").show();
  $("#speed-connect-btn").addClass("disabled");
  $("#speed-connect-btn").prop("disabled", true);
  $("#res-key-loading").css({ display: "flex" });

  getKeyDataFunction({
    speedCode,
  }).then((result) => {
    const { test_restricted_key = "", live_restricted_key = "" } = result;

    $("#speed_test_restricted_key").val(test_restricted_key);
    $("#speed_live_restricted_key").val(live_restricted_key);

    let data = {
      action: "speed_store_restricted",
      test_restricted_key: test_restricted_key,
      live_restricted_key: live_restricted_key,
    };

    jQuery
      .post(adminAjaxUrl, data, function (_response) {})
      .fail(function () {
        alert(
          "Error processing your request. Please make sure to enter a valid URL."
        );
      });

    $("#res-key-loading").css({ display: "none" });
    $("#res-key-generated").css({ display: "flex" });
    $(".step1").css({ background: "unset" });
    $(".step1")
      .html("")
      .append("<img src='" + successIcon + "'/>");
    $(".step1").removeClass("active");
    $(".step2").addClass("active");
    $("#setting-section").show();
    $("#generate-webhook-btn").css({ display: "flex" });
    for (const value of fields) {
      const field = document.getElementById(value.fieldName);
      if (field.value.trim() == "") {
        $("#generate-webhook-btn").addClass("disabled");
        $("#generate-webhook-btn").prop("disabled", true);
      } else {
        $("#generate-webhook-btn").removeClass("disabled");
        $("#generate-webhook-btn").prop("disabled", false);
      }
    }
  });
} else if (
  speedCode !== "" &&
  $("#speed_test_restricted_key").val() !== "" &&
  $("#speed_webhook_test_secret_key").val() === ""
) {
  $(".speed-steps").hide();
  if (
    $("#speed_plugin_enable_status").val() === "yes" &&
    $("#speed_plugin_transaction_mode").val() === "Test"
  ) {
    $(".speed-status").css({ display: "flex" });
  }
  $("#setting-section").show();
  $("#setting-section").css({ padding: 0 });
  $("#generate-webhook-btn").css({ display: "flex" });
  for (const value of fields) {
    const field = document.getElementById(value.fieldName);
    if (field.value.trim() == "") {
      $("#generate-webhook-btn").addClass("disabled");
      $("#generate-webhook-btn").prop("disabled", true);
    } else {
      $("#generate-webhook-btn").removeClass("disabled");
      $("#generate-webhook-btn").prop("disabled", false);
    }
  }
} else {
  $(".speed-steps").hide();
  if (
    $("#speed_plugin_enable_status").val() === "yes" &&
    $("#speed_plugin_transaction_mode").val() === "Test"
  ) {
    $(".speed-status").css({ display: "flex" });
  }
  $("#setting-section").show();
  $("#setting-section").css({ padding: 0 });
  $(".disconnect-btn").show();
  $("#speed_save_btn").show();
}

$("#speed-connect-btn").click(function (event) {
  event.preventDefault();
  $("#speed_connect_error").hide();

  const authURL = `${speedAuthUrl}client_id=${clientId}&redirect_url=${encodeURIComponent(
    speedPluginRedirectUrl
  )}`;

  window.location = authURL;
});

$("#speed_save_btn").click(function (event) {
  event.preventDefault();

  for (const value of fields) {
    const field = document.getElementById(value.fieldName);

    if (field.value.trim() == "") {
      field.style.borderColor = "red";
      $("#speed_error").show();
    } else {
      $(".woocommerce-save-button").click();
    }
  }
});

$(".disconnect-btn").click(function () {
  $("#disconnectModal").modal("show");
});

$("#continue-btn").click(function (event) {
  event.preventDefault();
  let data = {
    action: "speed_disconnect",
  };

  jQuery
    .post(adminAjaxUrl, data, function (response) {
      if (response.data.status == 1) {
        window.location.reload();
      }
    })
    .fail(function () {
      alert(
        "Error processing your request. Please make sure to enter a valid URL."
      );
    });
});

$("#generate-webhook-btn").click(function (event) {
  event.preventDefault();
  $("#webhook-section-loading").css({ display: "none" });
  $("#generate-webhook-btn").addClass("disabled");
  $("#webhook-section-loading").css({ display: "flex" });

  const test_restricted_key = $("#speed_test_restricted_key").val();
  const live_restricted_key = $("#speed_live_restricted_key").val();

  generateWebhookSecret({ test_restricted_key, live_restricted_key })
    .then((results) => {
      $("#speed_webhook_test_secret_key").val(results.testSecretKey);
      $("#speed_webhook_live_secret_key").val(results.liveSecretKey);
      const data = {
        action: "handle_speed_store_secret",
        webhook_test_secret: results.testSecretKey,
        webhook_live_secret: results.liveSecretKey,
      };
      jQuery
        .post(adminAjaxUrl, data, function (_response) {
          $("#webhook-section-loading").css({ display: "none" });
          $("#webhook-created").css({ display: "flex" });
          setTimeout(function () {
            $(".woocommerce-save-button").click();
          }, 1500);
        })
        .fail(function () {
          alert(
            "Error processing your request. Please make sure to enter a valid URL."
          );
        });
    })
    .catch((_error) => {
      $("#webhook-section-loading").css({ display: "none" });
      $("#generate-webhook-btn").addClass("disabled");
      $("#generate-webhook-btn").prop("disabled", true);
      $("#speed_error").show();
      $("#waitTime").text("30 seconds");
      $("html, body").animate({
        scrollTop: $("#speed_error").position().top,
      });
      var time = 30;
      var interval = setInterval(function () {
        time--;
        $("#waitTime").text(wp.i18n.__(time + " seconds"));

        $("#webhook-wait").show();
        $("#webhook-wait").text(
          wp.i18n.__("Please try after " + time + " seconds")
        );
        if (time === 0) {
          clearInterval(interval);
          $("#speed_error").hide();
          $("#webhook-wait").hide();
          $("#generate-webhook-btn").removeClass("disabled");
          $("#generate-webhook-btn").prop("disabled", false);
        }
      }, 1000);
    });
});

function checkVaidation() {
  for (const value of fields) {
    const field = document.getElementById(value.fieldName);
    if (field.value.trim() == "") {
      field.style.borderColor = "red";
      $("#generate-webhook-btn").addClass("disabled");
      $("#generate-webhook-btn").prop("disabled", true);
    } else {
      field.style.borderColor = "#e4e9ee";
      $("#generate-webhook-btn").removeClass("disabled");
      $("#generate-webhook-btn").prop("disabled", false);
    }
  }
}
