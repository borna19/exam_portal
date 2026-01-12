/* Google Apps Script (deploy as Web App - execute as: Me; who has access: Anyone)

This script accepts a POST request with JSON body { exam: {...}, questions: [...] }
Creates a Google Form from the questions and returns JSON with form URLs.
*/

function doPost(e) {
  try {
    var payload = e.postData.contents;
    var data = JSON.parse(payload);

    var exam = data.exam || {exam_name: 'Imported Exam'};
    var questions = data.questions || [];

    var form = FormApp.create(exam.exam_name + ' (Imported)');

    questions.forEach(function(q) {
      var title = q.question || 'Question';
      if (q.question_type === 'MCQ') {
        var choices = [];
        if (q.options) {
          for (var key in q.options) {
            if (q.options[key] && q.options[key].trim() !== '') choices.push(q.options[key]);
          }
        }
        if (choices.length === 0) {
          form.addTextItem().setTitle(title);
        } else {
          var item = form.addMultipleChoiceItem();
          item.setTitle(title).setChoices(choices.map(function(c){ return item.createChoice(c); }));
        }
      } else {
        form.addTextItem().setTitle(title);
      }
    });

    var resp = {
      formUrl: form.getPublishedUrl(),
      editUrl: form.getEditUrl()
    };

    return ContentService
      .createTextOutput(JSON.stringify(resp))
      .setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService
      .createTextOutput(JSON.stringify({ error: err.message }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}
