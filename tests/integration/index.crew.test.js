const querystring = require('querystring')
const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let defaultPage = null
let adminPage = null

const $eval = async (selector, func) => defaultPage.$eval(selector, func)
const type = async (selector, value) => defaultPage.type(selector, value)
const goto = async (url, waitForNetwork = false) => defaultPage.goto(url, waitForNetwork)
const clickLink = async (selector) => defaultPage.clickLink(selector)
const expectSuccessMessage = async (expected) => defaultPage.expectSuccessMessage(expected)
const expectErrorMessage = async (expected) => defaultPage.expectErrorMessage(expected)
const expectFormValue = async (selector, expected) => defaultPage.expectFormValue(selector, expected)
const expectElementCount = async (selector, expectedCount) => defaultPage.expectElementCount(selector, expectedCount)

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

    await adminPage.configureDefaultGroupStatus(competitionId, 'accepted')
  })

  afterAll(async () => {
    await adminPage.close()
    await defaultPage.close()
  })


  describe('Crew', () => {

    let crewGroupProps = null

    beforeAll(async () => {
      // Go to admin console
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Groups&tuja_competition=${competitionId}`)

      // Select crew category in tuja_new_group_type
      await adminPage.page.select('select[name="tuja_new_group_type"]', crewGroupCategoryId)

      // Type crew group name in tuja_new_group_name
      await adminPage.type('input[name="tuja_new_group_name"]', '_ The Regular Crew') // Underscore added to ensure group shown first in list(s)

      // Click correct tuja_action button
      await adminPage.clickLink('button[name="tuja_action"][value="group_create"]')

      // Wait for page to load and extract crew group id from link in group list
      const groupTableRow = await adminPage.$('table#tuja_groups_list > tbody > tr:first-child > td:first-child')
      const key = await groupTableRow.evaluate(node => node.dataset.groupKey)
      crewGroupProps = {
        key
      }

      await adminPage.configureEventDateLimits(competitionId, 7 * 24 * 60, 7 * 24 * 60 + 60)
    })

    describe('report score', () => {
      let competingGroupProps = null
      let stationScoreReportForm = 0

      const createForm = async () => {
        const formId = await createNewForm('The Stations')

        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${formId}`)

        await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
        await adminPage.clickLink('div.tuja-admin-question a[href*="FormQuestions"]')
        await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
        await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
        await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
        await adminPage.clickLink('#tuja_form_questions_back')
        await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
        await adminPage.clickLink('div.tuja-admin-question:nth-of-type(3) a[href*="FormQuestions"]')
        await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
        await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
        await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')

        return formId
      }

      beforeAll(async () => {
        competingGroupProps = await defaultPage.signUpTeam( adminPage)
        stationScoreReportForm = await createForm()
      })

      it('should be possible for crew member to report score', async () => {

        const goToForm = async () => {
          await defaultPage.goto(`http://localhost:8080/${crewGroupProps.key}/rapportera-poang/${stationScoreReportForm}`)
          // Select first form question group
          const formGroupId = await defaultPage.$eval('select#tuja_pointsshortcode__filter-questions > option:nth-child(2)', node => node.value)
          await defaultPage.page.select('#tuja_pointsshortcode__filter-questions', formGroupId)
          // Select recently created competing team
          await defaultPage.page.select('#tuja_pointsshortcode__filter-groups', competingGroupProps.id)
        }

        await goToForm()

        await defaultPage.expectElementCount('input.tuja-fieldtext', 3)

        await defaultPage.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.type('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '4')
        await defaultPage.clickLink('button[name="tuja_pointsshortcode__action"][value="update"]')

        await defaultPage.expectSuccessMessage('Poängen har sparats.')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '4')

        await goToForm()

        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '4')

        await defaultPage.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '9')
        await defaultPage.clickLink('button[name="tuja_pointsshortcode__action"][value="update"]')

        await goToForm()

        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '9')
      })

      it('should NOT be possible for competing team to view the form', async () => {
        await goto(`http://localhost:8080/${competingGroupProps.key}/rapportera-poang/${stationScoreReportForm}`)

        await expectErrorMessage('Bara funktionärer får använda detta formulär.')
        await expectElementCount('form', 0)
        await expectElementCount('button', 0)
        await expectElementCount('input', 0)
        await expectElementCount('select', 0)
      })

      it('should NOT be possible for two crew members to unknowingly overwrite each other\'s reported score', async () => {
        const initSession = async () => {
          const session = await createNewUserPage()
          await session.goto(`http://localhost:8080/${crewGroupProps.key}/rapportera-poang/${stationScoreReportForm}`)
          // Select first form question group
          const formGroupId = await session.$eval('select#tuja_pointsshortcode__filter-questions > option:nth-child(2)', node => node.value)
          await session.page.select('#tuja_pointsshortcode__filter-questions', formGroupId)
          // Select recently created competing team
          await session.page.select('#tuja_pointsshortcode__filter-groups', competingGroupProps.id)
          return session
        }
        const aliceSession = await initSession()
        const bobSession = await initSession()

        await aliceSession.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '1')
        await aliceSession.type('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '2')
        await aliceSession.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '3')
        await aliceSession.clickLink('button[name="tuja_pointsshortcode__action"][value="update"]')

        await aliceSession.expectSuccessMessage('Poängen har sparats.')

        await bobSession.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '4')
        await bobSession.type('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '5')
        await bobSession.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '6')
        await bobSession.clickLink('button[name="tuja_pointsshortcode__action"][value="update"]')

        await bobSession.expectErrorMessage('Någon annan har hunnit rapportera in andra poäng')

        await aliceSession.close()
        await bobSession.close()

        const verifySession = await initSession()
        await verifySession.page.waitForSelector('div.tuja-field')
        await verifySession.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '1')
        await verifySession.expectFormValue('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '2')
        await verifySession.expectFormValue('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '3')
        await verifySession.close()
      })
    })

    it('should be possible to sign up as crew member', async () => {
      // TODO: More positive test cases
      await goto(`http://localhost:8080/${crewGroupProps.key}/anmal-mig`)

      await type('input[name^="tuja-person__name"]', 'Carol')
      await type('input[name^="tuja-person__email"]', 'carol@example.com')
      await type('input[name^="tuja-person__phone"]', '070-12345678')
      await type('input[name^="tuja-person__food"]', 'Picky about eggs')
      await type('input[name^="tuja-person__note"]', 'Need to leave early')

      await clickLink('button[name="tuja-action"]')

      await expectSuccessMessage('Tack')

      const editPersonUrl = await $eval('#tuja_signup_success_edit_link', node => node.href)
      await goto(editPersonUrl)

      await expectFormValue('input[name^="tuja-person__name"]', 'Carol')
      await expectFormValue('input[name^="tuja-person__email"]', 'carol@example.com')
      await expectFormValue('input[name^="tuja-person__phone"]', '+467012345678')
      await expectFormValue('input[name^="tuja-person__food"]', 'Picky about eggs')
      await expectFormValue('input[name^="tuja-person__note"]', 'Need to leave early')
    })

    it.each([
      ['Trudy', '', '070-1234567', 'No fondness for spam', 'Need to leave early', 'E-postadressen ser konstig ut'] // Missing required field
      // TODO: More negative test cases
    ])('should not be possible to sign up as a crew member with bad data', async (name, email, phone, food, note, expectedErrorMessage) => {
      await goto(`http://localhost:8080/${crewGroupProps.key}/anmal-mig`)

      await type('input[name^="tuja-person__name"]', name)
      await type('input[name^="tuja-person__email"]', email)
      await type('input[name^="tuja-person__phone"]', phone)
      await type('input[name^="tuja-person__food"]', food)
      await type('input[name^="tuja-person__note"]', note)

      await clickLink('button[name="tuja-action"]')

      await expectErrorMessage(expectedErrorMessage)
    })
  })

})
