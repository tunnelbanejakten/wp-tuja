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
    defaultPage = await (new UserPageWrapper(browser, competitionId, competitionKey).init())

    await adminPage.configureDefaultGroupStatus(competitionId, 'accepted')
  })

  afterAll(async () => {
    await adminPage.close()
    await defaultPage.close()
  })


  describe('Crew', () => {

    let crewGroupProps = null
    let crewPersonProps = null

    beforeAll(async () => {
      crewGroupProps = {
        key: crewGroupKey
      }
      crewPersonProps = {
        key: crewPersonKey
      }

      await adminPage.configureEventDateLimits(competitionId, 7 * 24 * 60, 7 * 24 * 60 + 60)
    })

    describe('report score', () => {
      let competingGroupProps = null
      let stationScoreReportForm = 0

      beforeAll(async () => {
        competingGroupProps = await defaultPage.signUpTeam(adminPage)
        stationScoreReportForm = global.formId
        await adminPage.addTeam() // Make sure there are at least two teams in competition
      })

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
        const goToForm = async () => {
          await defaultPage.goto(`http://localhost:8080/${crewPersonProps.key}/rapportera`)
          await defaultPage.clickLink('form p:nth-of-type(1) a') // Select first station
        }
        await goToForm()

        await defaultPage.type('div.tuja-field:nth-of-type(1) input.tuja-fieldtext', '1')
        await defaultPage.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')
        await defaultPage.clickLink('button[name="tuja_crewview__action"][value="update"]')

        await defaultPage.expectSuccessMessage('Poängen har sparats.')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(1) input.tuja-fieldtext', '1')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')

        await goToForm()

        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(1) input.tuja-fieldtext', '1')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '2')

        await defaultPage.type('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '9')
        await defaultPage.clickLink('button[name="tuja_crewview__action"][value="update"]')

        await goToForm()

        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(1) input.tuja-fieldtext', '1')
        await defaultPage.expectFormValue('div.tuja-field:nth-of-type(2) input.tuja-fieldtext', '9')
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
        const initSession = async () => {
          const session = await createNewUserPage()
          await session.goto(`http://localhost:8080/${crewPersonProps.key}/rapportera`)
          await session.clickLink('div.entry-content p:nth-of-type(1) a') // Select first station
          return session
        }
        const aliceSession = await initSession()
        const bobSession = await initSession()

        await aliceSession.type('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '20')
        await aliceSession.click('header.entry-header') // Click outside to trigger onChange event
        await aliceSession.wait(1000 + 2000) // 1 second for debounce and 2 seconds for API response
        const errorMessageContainerDisplay0 = await aliceSession.$eval('#tuja-report-points-warning-message-container', node => node.style.display)
        expect(errorMessageContainerDisplay0).toEqual('none')

        await bobSession.type('section.tuja-team-score-container:nth-of-type(2) input.tuja-fieldtext', '5')
        await bobSession.click('header.entry-header') // Click outside to trigger onChange event
        await bobSession.wait(1000 + 2000) // 1 second for debounce and 2 seconds for API response
        const errorMessageContainerDisplay1 = await bobSession.$eval('#tuja-report-points-warning-message-container', node => node.style.display)
        expect(errorMessageContainerDisplay1).toEqual('flex')
        const errorMessage = await bobSession.$eval('.tuja-message-error', node => node.innerHTML)
        expect(errorMessage).toContain('Någon annan har hunnit rapportera in andra poäng')
        await bobSession.click('#tuja-report-points-warning-message-button')
        const errorMessageContainerDisplay2 = await bobSession.$eval('#tuja-report-points-warning-message-container', node => node.style.display)
        expect(errorMessageContainerDisplay2).toEqual('none')

        await bobSession.type('section.tuja-team-score-container:nth-of-type(1) input.tuja-fieldtext', '10')
        await bobSession.click('header.entry-header') // Click outside to trigger onChange event
        await bobSession.wait(1000 + 2000) // 1 second for debounce and 2 seconds for API response
        const errorMessageContainerDisplay3 = await bobSession.$eval('#tuja-report-points-warning-message-container', node => node.style.display)
        expect(errorMessageContainerDisplay3).toEqual('none')

        await aliceSession.close()
        await bobSession.close()

        const verifySession = await initSession()
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
