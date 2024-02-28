/**
 * Handle Twak.To
 *
 */

// const WEB_HOOK_URL = "https://hooks.slack.com/services/*****/*****/******/"

/**
 * listener function for post request from Slack with book name
 */
function doPost(e) {
  if (typeof e !== 'undefined') { 
    
    // setup the Sheet
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName('Sheet1');
    var lastRow = sheet.getLastRow();

    // paste the slack details to the sheet
    sheet.getRange(lastRow + 1, 1).setValue(e.postData.contents);

    // extract the relevant data
    var parameter = JSON.parse(e.postData.contents);
    var userName = parameter.requester.email;
    var text_to_send = ` ${parameter.ticket.subject} \n ${parameter.ticket.message}`;
    var date = new Date();
  
 
    
    // retrieve the book details
    // var bookData = getBookDetails(bookName);
    // return sendSlackMessage(bookName, userName);

    // check if we were able to retrieve book details
      var bookData = [];

    try {

  var payload = {
    "channel" : "#slackbot-test",
    "username" : "My Test Slackbot",
    "icon_url" : "https://puu.sh/BQqA9/408cadc2b3.png",
    "text" : `Here is the new Tawk.to ticket submission *${parameter.ticket.humanId}*`,
    "attachments": [{
      "text": `:memo: New Ticket Submission From *${parameter.property.name}* \n *${userName}* \n ${text_to_send}`,
      "footer": `<https://dashboard.tawk.to/#/inbox/${parameter.property.id}/all/ticket/${parameter.ticket.id}|Reply script>`,
      "mrkdwn_in": ["text"]
    }]
  }
 
  var options = {
    "method" : "post",
    "contentType" : "application/json",
    "payload" : JSON.stringify(payload)
  };
 
  var results = UrlFetchApp.fetch(WEB_HOOK_URL, options)
    }
    catch(e) {
        Logger.log('Unable to fetch book data. Hint: ' + e);
    }

      // return message when book successfully found
        var result = {
            'text': ':books::nerd_face: Thank you for your standup! :tada:',
        }
        return ContentService.createTextOutput(JSON.stringify(result)).setMimeType(ContentService.MimeType.JSON);
    
  }
}
function doGet(e) {
      // return message when book successfully found
        var result = {
            'text': ':books::nerd_face: Thank you for your standup! :tada:',
        }
        return ContentService.createTextOutput(JSON.stringify(result)).setMimeType(ContentService.MimeType.JSON);

}