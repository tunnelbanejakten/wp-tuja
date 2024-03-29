const querystring = require('querystring')
const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let defaultPage = null
let adminPage = null

jest.setTimeout(300000)

describe('Review Answers', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null

  const createNewUserPage = async () => (new UserPageWrapper(browser, competitionId, competitionKey)).init()

  const createNewForm = async (formName) => {
    await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Forms&tuja_competition=${competitionId}`)

    await adminPage.type('#tuja_form_name', formName)
    await adminPage.clickLink('#tuja_form_create_button')

    const { id, key } = await adminPage.$eval(`span#tuja_new_form_message`, node => ({ id: node.dataset.formId, key: node.dataset.formRandomId }))

    return { id, key }
  }

  beforeAll(async () => {
    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    adminPage = await (new AdminPageWrapper(browser).init())
    defaultPage = await (new UserPageWrapper(browser, competitionId, competitionKey).init())
  })

  afterAll(async () => {
    await adminPage.close()
    await defaultPage.close()
  })

  describe.skip('Reviewing answers', () => {
    let formKey = 0
    let formId = 0
    let numberQuestionId = 0
    let choiceQuestionId = 0
    let imagesQuestionId = 0
    let textQuestion1Id = 0
    let textQuestion2Id = 0

    const createForm = async () => {
      const { id, key } = await createNewForm('The Form to Review')

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${id}`)

      await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
      const { id: questionGroupId } = await adminPage.$eval(`span#tuja_new_question_group_message`, node => ({ id: node.dataset.questionGroupId }))

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=FormQuestionGroup&tuja_competition=${competitionId}&tuja_form=${id}&tuja_question_group=${questionGroupId}`)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=FormQuestionGroup&tuja_competition=${competitionId}&tuja_form=${id}&tuja_question_group=${questionGroupId}`)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__choices"]')
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=FormQuestionGroup&tuja_competition=${competitionId}&tuja_form=${id}&tuja_question_group=${questionGroupId}`)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__images"]')
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=FormQuestionGroup&tuja_competition=${competitionId}&tuja_form=${id}&tuja_question_group=${questionGroupId}`)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=FormQuestionGroup&tuja_competition=${competitionId}&tuja_form=${id}&tuja_question_group=${questionGroupId}`)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')

      const ids = await adminPage.$$eval('div[data-field-id]', nodes => nodes.map(node => node.dataset.fieldId.substr('tuja-question__'.length)))

      numberQuestionId = ids[0]
      choiceQuestionId = ids[1]
      imagesQuestionId = ids[2]
      textQuestion1Id = ids[3]
      textQuestion2Id = ids[4]

      await adminPage.page.waitForSelector(`textarea[name="JSONEditor_tuja-question__${numberQuestionId}[text]"]`)
      await adminPage.page.waitForSelector(`textarea[name="JSONEditor_tuja-question__${choiceQuestionId}[text]"]`)
      await adminPage.page.waitForSelector(`textarea[name="JSONEditor_tuja-question__${imagesQuestionId}[text]"]`)
      await adminPage.page.waitForSelector(`textarea[name="JSONEditor_tuja-question__${textQuestion1Id}[text]"]`)
      await adminPage.page.waitForSelector(`textarea[name="JSONEditor_tuja-question__${textQuestion2Id}[text]"]`)

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

      return ({ id, key })
    }

    beforeAll(async () => {
      const formIds = await createForm()
      formKey = formIds.key
      formId = formIds.id
    })

    const checkScores = async (...expectations) => {
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Scoreboard&tuja_competition=${competitionId}`)
      for (const { groupId, expectedScore } of expectations) {
        expect(await adminPage.$eval(`#tuja-scoreboard-group-${groupId}-points`, node => node.dataset.score)).toEqual(String(expectedScore))
      }
    }

    const submitAnswers = async (page,
      groupKey,
      numberQuestionAnswer = null,
      choiceQuestionAnswer = null,
      imagesQuestionAnswer = null,
      textQuestion1Answer = null,
      textQuestion2Answer = null) => {
      await page.goto(`http://localhost:8080/${groupKey}/svara/${formKey}`, true)

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

    it('type manual score', async () => {
      const alicePage = await createNewUserPage()
      const groupAliceProps = await alicePage.signUpTeam(adminPage)

      const bobPage = await createNewUserPage()
      const groupBobProps = await bobPage.signUpTeam(adminPage)

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
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Scoreboard&tuja_competition=${competitionId}`)

      expect(await adminPage.$eval(`#tuja-scoreboard-group-${groupAliceProps.id}-points`, node => node.dataset.score)).toEqual('30')

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Review&tuja_competition=${competitionId}`)

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
    it('only latest answers are scored and reviewed', async () => {
      const groupPage = await createNewUserPage()
      const groupProps = await groupPage.signUpTeam(adminPage)

      // Team submits incorrect answer
      await submitAnswers(
        groupPage,
        groupProps.key,
        '10000',
      )
      // Scoreboard shows 0 points, since the answer was incorrect.
      await checkScores({
        groupId: groupProps.id,
        expectedScore: 0
      })
      // Team submits CORRECT answer
      await submitAnswers(
        groupPage,
        groupProps.key,
        '40075',
      )
      // Scoreboard shows 10 points, since the answer was correct this time.
      // It doesn't matter that the response hasn't been reviewed.
      await checkScores({
        groupId: groupProps.id,
        expectedScore: 10
      })

      // Verify that Review page shows the latest response from the team.
      // And the correct score from the auto-correcter.
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Review&tuja_competition=${competitionId}`)
      await adminPage.expectToContain(`#tuja_review_response_container__${numberQuestionId}__${groupProps.id}`, '40\xa0075')
      await adminPage.expectToContain(`#tuja_review_auto_score__${numberQuestionId}__${groupProps.id} span.tuja-admin-review-autoscore`, '10')
      await adminPage.clickLink('button[name="tuja_review_action"][value="save"]')

      // Verify that page is cleared/empty after review is completed
      await adminPage.expectElementCount('p.tuja-admin-review-form-empty', 1)
      await adminPage.expectToContain('p.tuja-admin-review-form-empty', 'Det finns inget att visa.')
      await adminPage.expectElementCount('button[name="tuja_review_action"][value="save"]', 0)

      // Scoreboard (still) shows 10 points.
      await checkScores({
        groupId: groupProps.id,
        expectedScore: 10
      })
      // Team changed their mind and submits INCORRECT answer
      await submitAnswers(
        groupPage,
        groupProps.key,
        '10001',
      )
      // Team chanes back to the CORRECT answer
      await submitAnswers(
        groupPage,
        groupProps.key,
        '40075',
      )

      // Verify that Review page shows the latest response from the team.
      // And the correct score from the auto-correcter.
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Review&tuja_competition=${competitionId}`)
      await adminPage.expectElementCount('p.tuja-admin-review-form-empty', 0)
      await adminPage.expectToContain(`#tuja_review_response_container__${numberQuestionId}__${groupProps.id}`, '40\xa0075')
      await adminPage.expectToContain(`#tuja_review_auto_score__${numberQuestionId}__${groupProps.id} span.tuja-admin-review-autoscore`, '10')
      await adminPage.expectElementCount('button[name="tuja_review_action"][value="save"]', 1)

      // Team changed their mind again and submits another INCORRECT answer
      await submitAnswers(
        groupPage,
        groupProps.key,
        '10002',
      )

      // Verify that Review page shows the latest response from the team.
      // And the correct score from the auto-correcter.
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Review&tuja_competition=${competitionId}`)
      await adminPage.expectToContain(`#tuja_review_response_container__${numberQuestionId}__${groupProps.id}`, '10\xa0002')
      await adminPage.expectToContain(`#tuja_review_auto_score__${numberQuestionId}__${groupProps.id} span.tuja-admin-review-autoscore`, '0')

      // Scoreboard shows 0 points, since the most recent answer was incorrect.
      await checkScores({
        groupId: groupProps.id,
        expectedScore: 0
      })
    })
  })

})
