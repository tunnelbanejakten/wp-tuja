const querystring = require('querystring')
const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let defaultPage = null
let adminPage = null

describe('wp-tuja', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null
  let crewGroupCategoryId = null

  const createNewUserPage = async () => (new UserPageWrapper(browser, competitionId, competitionKey)).init()

  const createNewForm = async (formName) => {
    await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Competition&tuja_competition=${competitionId}`)

    await adminPage.type('#tuja_form_name', formName)
    await adminPage.clickLink('#tuja_form_create_button')

    const links = await adminPage.page.$$('form.tuja a')
    let link = null
    for (let i = 0; i < links.length; i++) {
      const el = links[i]
      const linkText = await el.evaluate(node => node.innerText)
      const isEqual = linkText === formName
      if (isEqual) {
        link = el
        break
      }
    }

    const formId = querystring.parse(await link.evaluate(node => node.href)).tuja_form
    return formId
  }

  beforeAll(async () => {

    jest.setTimeout(300000)

    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    crewGroupCategoryId = global.crewGroupCategoryId
    adminPage = await (new AdminPageWrapper(browser).init())
    defaultPage = await (new UserPageWrapper(browser, competitionId, competitionKey).init())
  })

  describe('Reviewing answers', () => {
    let formId = null
    let numberQuestionId = 0
    let choiceQuestionId = 0
    let imagesQuestionId = 0
    let textQuestion1Id = 0
    let textQuestion2Id = 0

    const createForm = async () => {
      const formId = await createNewForm('The Form to Review')

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${formId}`)

      await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
      await adminPage.clickLink('div.tuja-admin-question a[href*="FormQuestions"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__choices"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__images"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')

      const ids = await adminPage.$$eval('div[data-field-id]', nodes => nodes.map(node => node.dataset.fieldId.substr('tuja-question__'.length)))

      numberQuestionId = ids[0]
      choiceQuestionId = ids[1]
      imagesQuestionId = ids[2]
      textQuestion1Id = ids[3]
      textQuestion2Id = ids[4]

      await adminPage.page.waitForSelector(`input[name="JSONEditor_tuja-question__${numberQuestionId}[text]"]`)
      await adminPage.page.waitForSelector(`input[name="JSONEditor_tuja-question__${choiceQuestionId}[text]"]`)
      await adminPage.page.waitForSelector(`input[name="JSONEditor_tuja-question__${imagesQuestionId}[text]"]`)
      await adminPage.page.waitForSelector(`input[name="JSONEditor_tuja-question__${textQuestion1Id}[text]"]`)
      await adminPage.page.waitForSelector(`input[name="JSONEditor_tuja-question__${textQuestion2Id}[text]"]`)

      // How many kilometers long is the equator? (number)
      await adminPage.$eval(`#tuja-question__${numberQuestionId}`, node => node.value = JSON.stringify({
        'text': 'How many kilometers long is the equator?',
        'text_hint': 'A subtle hint or reminder.',
        'score_max': 10,
        'sort_order': '0',
        'correct_answer': 40075
      }))

      // Which of these actors voiced characters in Shrek? (multiple correct options)
      await adminPage.$eval(`#tuja-question__${choiceQuestionId}`, node => node.value = JSON.stringify({
        'text': 'Which of these actors voiced characters in Shrek?',
        'text_hint': 'A subtle hint or reminder.',
        'score_max': 10,
        'sort_order': '0',
        'possible_answers': [
          'Mike Myers',
          'Eddie Murphy',
          'Cameron Diaz',
          'Kristen Bell',
          'Idina Menzel',
          'Jonathan Groff',
          'Josh Gad'
        ],
        'correct_answers': [
          'Mike Myers',
          'Eddie Murphy',
          'Cameron Diaz'
        ],
        'is_single_select': false,
        'score_type': 'all_of'
      }))

      // Take a picture of something cute (image, test manual points)
      await adminPage.$eval(`#tuja-question__${imagesQuestionId}`, node => node.value = JSON.stringify({
        'text': 'Take a picture of something cute',
        'text_hint': 'A subtle hint or reminder.',
        'score_max': 10,
        'sort_order': '0'
      }))

      // Who invented the windshield wiper? (freetext, test spelling, test autoscore, test manual points)
      await adminPage.$eval(`#tuja-question__${textQuestion1Id}`, node => node.value = JSON.stringify({
        'text': 'Who invented the windshield wiper?',
        'text_hint': 'A subtle hint or reminder.',
        'score_max': 10,
        'sort_order': '0',
        'correct_answers': ['Mary Anderson'],
        'incorrect_answers': [],
        'is_single_answer': true,
        'score_type': 'one_of'
      }))

      // Which are the capitals of the nordic countries? (multiple correct freetext, test spelling)
      await adminPage.$eval(`#tuja-question__${textQuestion2Id}`, node => node.value = JSON.stringify({
        'text': 'Which are the capitals of the five nordic countries?',
        'text_hint': 'A subtle hint or reminder.',
        'score_max': 10,
        'sort_order': '0',
        'correct_answers': ['stockholm', 'copenhagen', 'oslo', 'helsinki', 'reykjavik'],
        'incorrect_answers': [],
        'is_single_answer': false,
        'score_type': 'unordered_percent_of'
      }))

      await adminPage.clickLink('button[name="tuja_action"][value="questions_update"]')

      return formId
    }

    beforeAll(async () => {
      formId = await createForm()
    })

    it('type manual score', async () => {
      const checkScores = async (...expectations) => {
        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Scoreboard&tuja_competition=${competitionId}`)
        for (const { groupId, expectedScore } of expectations) {
          expect(await adminPage.$eval(`#tuja-scoreboard-group-${groupId}-points`, node => node.dataset.score)).toEqual(String(expectedScore))
        }
      }

      const submitAnswers = async (page,
                                   groupKey,
                                   numberQuestionAnswer,
                                   choiceQuestionAnswer,
                                   imagesQuestionAnswer,
                                   textQuestion1Answer,
                                   textQuestion2Answer) => {
        await page.goto(`http://localhost:8080/${groupKey}/svara/${formId}`, true)

        if (numberQuestionAnswer != null) {
          await page.type(`#tuja_formshortcode__response__${numberQuestionId}`, numberQuestionAnswer)
        }
        if (choiceQuestionAnswer != null) {
          await page.page.select(`#tuja_formshortcode__response__${choiceQuestionId}`, ...choiceQuestionAnswer)
        }
        if (imagesQuestionAnswer != null) {
          await page.chooseFiles(imagesQuestionAnswer)
        }
        if (textQuestion1Answer != null) {
          await page.type(`#tuja_formshortcode__response__${textQuestion1Id}`, textQuestion1Answer)
        }
        if (textQuestion2Answer != null) {
          await page.type(`#tuja_formshortcode__response__${textQuestion2Id}`, textQuestion2Answer)
        }

        await page.clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      }

      const alicePage = await createNewUserPage()
      const groupAliceProps = await alicePage.signUpTeam( adminPage)

      const bobPage = await createNewUserPage()
      const groupBobProps = await bobPage.signUpTeam( adminPage)

      await submitAnswers(
        alicePage,
        groupAliceProps.key,
        // Correct:
        '40075',
        // One missing (-10 p):
        ['Eddie Murphy', 'Cameron Diaz'],
        // No images:
        null,
        // Correct:
        'Mary Anderson',
        // Correct:
        'stockholm, copenhagen, oslo, helsinki, reykjavik')

      await submitAnswers(
        bobPage,
        groupBobProps.key,
        // One off. No points. (-10 p):
        '40074',
        // Entirely correct:
        ['Mike Myers', 'Eddie Murphy', 'Cameron Diaz'],
        // No images:
        ['pexels-photo-1578484.jpeg'],
        // Misspelled but still good enough:
        'Marie Andersson',
        // 1 out of 5 correct answers is missing (-2 p):
        'stockholm, copenhagen, oslo, helsinki')
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Scoreboard&tuja_competition=${competitionId}`)

      expect(await adminPage.$eval(`#tuja-scoreboard-group-${groupAliceProps.id}-points`, node => node.dataset.score)).toEqual('30')

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Review&tuja_competition=${competitionId}`)

      await adminPage.type(`#tuja_review_points__${choiceQuestionId}__${groupAliceProps.id}`, '5')
      await adminPage.type(`#tuja_review_points__${imagesQuestionId}__${groupBobProps.id}`, '1')

      await adminPage.clickLink('button[name="tuja_review_action"][value="save"]')

      await checkScores(
        {
          groupId: groupAliceProps.id,
          expectedScore: 35 // 10 + 5 + 0 + 10 + 10
        },
        {
          groupId: groupBobProps.id,
          expectedScore: 29 // 0 + 10 + 1 + 10 + 8
        })

      await submitAnswers(
        alicePage,
        groupAliceProps.key,
        null,
        // Change to correct answer (manual score should be ignored):
        ['Mike Myers', 'Eddie Murphy', 'Cameron Diaz'],
        null,
        null,
        null)
      await submitAnswers(
        bobPage,
        groupBobProps.key,
        // Change to correct answer:
        '40075',
        null,
        null,
        null,
        null)

      await checkScores(
        {
          groupId: groupAliceProps.id,
          expectedScore: 40 // 10 + 10 + 0 + 10 + 10
        },
        {
          groupId: groupBobProps.id,
          expectedScore: 39 // 10 + 10 + 1 + 10 + 8
        })

      await alicePage.close()
      await bobPage.close()
    })
    it.skip('set score for photo', () => {

    })
    it.skip('click manual score', () => {

    })
    it.skip('accept automatic score', () => {

    })
    it.skip('only review answers where auto-correcter is unsure', () => {

    })
    it.skip('add rejected answer as accepted one', () => {

    })
    it.skip('click manual score and then clear the field (automatic score should then be used)', () => {

    })
    it.skip('reviewed answers are only shown once', () => {

    })
  })

})
