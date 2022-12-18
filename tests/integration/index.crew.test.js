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

jest.setTimeout(300000)

describe('Crew', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null
  let crewGroupKey = null
  let crewPersonKey = null

  const createNewUserPage = async () => (new UserPageWrapper(browser, competitionId, competitionKey)).init()

  beforeAll(async () => {
    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    crewGroupKey = global.crewGroupKey
    crewPersonKey = global.crewPersonKey
    adminPage = await (new AdminPageWrapper(browser).init())
    defaultPage = await createNewUserPage()

    await adminPage.configureDefaultGroupStatus(competitionId, 'accepted')
  })

  afterAll(async () => {
    await adminPage.close()
    await defaultPage.close()
  })


  describe('Crew', () => {

    let crewGroupProps = null
    let crewPersonProps = null
    let competingGroupProps = null

    beforeAll(async () => {
      crewGroupProps = {
        key: crewGroupKey
      }
      crewPersonProps = {
        key: crewPersonKey
      }

      await adminPage.configureEventDateLimits(competitionId, 7 * 24 * 60, 7 * 24 * 60 + 60)
      competingGroupProps = await defaultPage.signUpTeam(adminPage)
    })

    describe('report score', () => {
      let stationScoreReportForm = 0

      beforeAll(async () => {
        stationScoreReportForm = global.formId
        await adminPage.addTeam() // Make sure there are at least two teams in competition
      })

      const goToReportStationForm = async (page) => {
        await page.goto(`http://localhost:8080/${crewPersonProps.key}/rapportera`)
        await page.clickLink('div.entry-content p:nth-of-type(1) a') // Select first station
      }

      const typePointsAndWait = async (page, fieldNumber, points, expectWarningMessage = false) => {
        await page.type(`section.tuja-team-score-container:nth-of-type(${fieldNumber}) input.tuja-fieldtext`, String(points))
        await page.click('header.entry-header') // Click outside to trigger onChange event
        await page.wait(1000 + 2000) // 1 second for debounce and 2 seconds for API response
        await page.expectElementCount(`#tuja-report-points-warning-message-container.${expectWarningMessage ? 'show' : 'hide'}`, 1)
      }

      it('should be possible for crew member to report score for questions', async () => {
        const goToForm = async () => {
          await defaultPage.goto(`http://localhost:8080/${crewGroupProps.key}/rapportera-poang/${stationScoreReportForm}`)
          // Select first form question group
          const formGroupId = await defaultPage.$eval('select#tuja_crewview__filter-questions > option:nth-child(2)', node => node.value)
          await defaultPage.page.select('#tuja_crewview__filter-questions', formGroupId)
          // Select recently created competing team
          await defaultPage.page.select('#tuja_crewview__filter-groups', competingGroupProps.id)
        }
        await goToForm()

        await defaultPage.expectElementCount('input.tuja-fieldtext', 4)

        await defaultPage.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.type('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '4')
        await defaultPage.clickLink('button[name="tuja_crewview__action"][value="update"]')

        await defaultPage.expectSuccessMessage('Poängen har sparats.')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '4')

        await goToForm()

        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '4')

        await defaultPage.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '9')
        await defaultPage.clickLink('button[name="tuja_crewview__action"][value="update"]')

        await goToForm()

        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '3')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '9')
      })

      it('should be possible for crew member to report points for station', async () => {
        await goToReportStationForm(defaultPage)

        await typePointsAndWait(defaultPage, 1, 1, false)
        await typePointsAndWait(defaultPage, 2, 2, false)

        await defaultPage.expectFormValue('section.tuja-team-score-container:nth-of-type(1) input.tuja-fieldtext', '1')
        await defaultPage.expectFormValue('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '2')

        await goToReportStationForm(defaultPage)

        await defaultPage.expectFormValue('section.tuja-team-score-container:nth-of-type(1) input.tuja-fieldtext', '1')
        await defaultPage.expectFormValue('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '2')

        await typePointsAndWait(defaultPage, 2, 9, false)

        await goToReportStationForm(defaultPage)
        await defaultPage.expectFormValue('section.tuja-team-score-container:nth-of-type(1) input.tuja-fieldtext', '1')
        await defaultPage.expectFormValue('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '9')
      })

      it.each([
        ['team to view the question-scoring form', () => `http://localhost:8080/${competingGroupProps.key}/rapportera-poang/${stationScoreReportForm}`],
        ['team to view the station-scoring form', () => `http://localhost:8080/${competingGroupProps.key}/rapportera`]
      ])('should NOT be possible for competing %s', async (_, urlFunction) => {
        await goto(urlFunction())

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
          const formGroupId = await session.$eval('select#tuja_crewview__filter-questions > option:nth-child(2)', node => node.value)
          await session.page.select('#tuja_crewview__filter-questions', formGroupId)
          // Select recently created competing team
          await session.page.select('#tuja_crewview__filter-groups', competingGroupProps.id)
          return session
        }
        const aliceSession = await initSession()
        const bobSession = await initSession()

        await aliceSession.takeScreenshot()
        await aliceSession.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '1')
        await aliceSession.type('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '2')
        await aliceSession.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '3')
        await aliceSession.clickLink('button[name="tuja_crewview__action"][value="update"]')

        await aliceSession.expectSuccessMessage('Poängen har sparats.')

        await bobSession.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '4')
        await bobSession.type('div.tuja-field:nth-of-type(3) input.tuja-fieldtext', '5')
        await bobSession.type('div.tuja-field:nth-of-type(4) input.tuja-fieldtext', '6')
        await bobSession.clickLink('button[name="tuja_crewview__action"][value="update"]')

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

      it('should NOT be possible for two crew members to unknowingly overwrite each other\'s reported station points', async () => {
        const aliceSession = defaultPage
        await goToReportStationForm(aliceSession)
        const bobSession = await createNewUserPage()
        await goToReportStationForm(bobSession)

        await aliceSession.type('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '20')
        await aliceSession.click('header.entry-header') // Click outside to trigger onChange event
        await aliceSession.wait(1000 + 2000) // 1 second for debounce and 2 seconds for API response
        await aliceSession.expectElementCount('#tuja-report-points-warning-message-container.hide', 1)

        await bobSession.type('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '5')
        await bobSession.click('header.entry-header') // Click outside to trigger onChange event
        await bobSession.wait(1000 + 2000) // 1 second for debounce and 2 seconds for API response
        await bobSession.expectElementCount('#tuja-report-points-warning-message-container.show', 1)

        await bobSession.expectToContain('.tuja-message-error', 'Någon annan har hunnit rapportera in andra poäng')
        await bobSession.click('#tuja-report-points-warning-message-button')
        await bobSession.expectElementCount('#tuja-report-points-warning-message-container.hide', 1)

        await bobSession.type('section.tuja-team-score-container:nth-of-type(1) input.tuja-fieldtext', '10')
        await bobSession.click('header.entry-header') // Click outside to trigger onChange event
        await bobSession.wait(1000 + 2000) // 1 second for debounce and 2 seconds for API response
        await bobSession.expectElementCount('#tuja-report-points-warning-message-container.hide', 1)

        // await aliceSession.close()
        await bobSession.close()

        const verifySession = await createNewUserPage()
        await goToReportStationForm(verifySession)
        await verifySession.expectFormValue('section.tuja-team-score-container:nth-of-type(1) input.tuja-fieldtext', '10')
        await verifySession.expectFormValue('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '20')
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

    it('should be possible to sign up as crew member using referral link', async () => {
      await goto(`http://localhost:8080/${crewGroupProps.key}/anmal-mig/?ref=${competingGroupProps.key}`)

      // Check if "thank you on behalf of referring team" message is shown.
      await expectSuccessMessage(`Som tack för att du anmäler dig som funktionär så kommer ${competingGroupProps.name} få en liten bonus på tävlingsdagen.`)

      await type('input[name^="tuja-person__name"]', 'Dave')
      await type('input[name^="tuja-person__email"]', 'dave@example.com')
      await type('input[name^="tuja-person__phone"]', '070-12345678')

      await clickLink('button[name="tuja-action"]')


      await expectSuccessMessage('Tack')

      // Check if check-in report mentions that a crew member has been "recruited".
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?action=tuja_report&tuja_competition=${competitionId}&tuja_view=ReportCheckInOut&tuja_report_format=html`)
      await adminPage.expectToContain(`#group-referral-count-${competingGroupProps.key}`, '(1 funk)')
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
