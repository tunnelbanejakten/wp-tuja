const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let defaultPage = null
let adminPage = null

const click = async (selector) => defaultPage.page.click(selector)
const $eval = async (selector, func) => defaultPage.$eval(selector, func)
const type = async (selector, value) => defaultPage.type(selector, value)
const goto = async (url, waitForNetwork = false) => defaultPage.goto(url, waitForNetwork)
const clickLink = async (selector) => defaultPage.clickLink(selector)
const expectSuccessMessage = async (expected) => defaultPage.expectSuccessMessage(expected)
const expectInfoMessage = async (expected) => defaultPage.expectInfoMessage(expected)
const expectWarningMessage = async (expected) => defaultPage.expectWarningMessage(expected)
const expectErrorMessage = async (expected) => defaultPage.expectErrorMessage(expected)
const expectFormValue = async (selector, expected) => defaultPage.expectFormValue(selector, expected)
const expectPageTitle = async (expected) => defaultPage.expectPageTitle(expected)
const expectElementCount = async (selector, expectedCount) => defaultPage.expectElementCount(selector, expectedCount)

describe('wp-tuja', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null
  let crewGroupCategoryId = null

  beforeAll(async () => {

    jest.setTimeout(300000)

    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    crewGroupCategoryId = global.crewGroupCategoryId
    adminPage = await (new AdminPageWrapper(browser).init())
    defaultPage = await (new UserPageWrapper(browser, competitionId, competitionKey).init())
  })

  describe('Signing up as new team', () => {

    describe('when teams need to be approved', () => {

      let groupProps = null

      beforeAll(async () => {
        await adminPage.configureDefaultGroupStatus(competitionId, 'awaiting_approval')

        groupProps = await defaultPage.signUpTeam(adminPage, false)

        await adminPage.configureEventDateLimits(competitionId, 7 * 24 * 60, 7 * 24 * 60 + 60)
      })

      afterAll(async () => {
        await adminPage.configureDefaultGroupStatus(competitionId, 'accepted')
      })

      it('the team portal becomes available after team has been accepted', async () => {
        const toBeAcceptedGroup = await defaultPage.signUpTeam(adminPage, false)

        await goto(toBeAcceptedGroup.portalUrl)
        await expectWarningMessage('Ert lag står på väntelistan')
        await expectElementCount('div.entry-content p > a', 0) // No links shown
        await expectElementCount('div.entry-content p.tuja-message-success', 0)
        await expectElementCount('div.entry-content p.tuja-message-warning', 1)
        await expectElementCount('div.entry-content p.tuja-message-error', 0)

        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Group&tuja_competition=${competitionId}&tuja_group=${toBeAcceptedGroup.id}`)
        await adminPage.clickLink('button[name="tuja_points_action"][value="transition__accepted"]')

        await goto(toBeAcceptedGroup.portalUrl)
        await expectElementCount('div.entry-content p > a', 4)
        await expectElementCount('div.entry-content p.tuja-message-success', 0)
        await expectElementCount('div.entry-content p.tuja-message-warning', 0)
        await expectElementCount('div.entry-content p.tuja-message-error', 0)
      })

      it.each([
        '',
        'andra',
        'andra-personer',
        'biljetter',
        'anmal-mig',
      ])('should NOT be possible to do anything on team page /%s', async (urlSuffix) => {
        await goto(`http://localhost:8080/${groupProps.key}/${urlSuffix}`)

        await expectWarningMessage('Ert lag står på väntelistan')

        await expectElementCount('div.entry-content p > a', 0) // No links shown
        await expectElementCount('div.entry-content form', 0) // No forms shown
        await expectElementCount('div.entry-content button', 0) // No buttons shown
      })
    })

    describe('when all teams get accepted automatically', () => {
      let groupProps = null

      beforeAll(async () => {
        groupProps = await defaultPage.signUpTeam(adminPage)

        await adminPage.configureEventDateLimits(competitionId, 7 * 24 * 60, 7 * 24 * 60 + 60)
      })

      it('team portal is accessible', async () => {
        expect(groupProps.portalUrl).toBe(`http://localhost:8080/${groupProps.key}`)
        await goto(groupProps.portalUrl)
        await expectPageTitle(`Hej ${groupProps.name}`)
      })

      it('should be possible to change name and to change category and to set team note', async () => {
        const newName = `New and improved ${groupProps.name}`

        await goto(groupProps.portalUrl)
        await clickLink('#tuja_edit_group_link')
        await click('input[name="tuja-group__age"]')
        await type('#tuja-group__name', newName)
        await type('#tuja-group__note', 'We will arrive a bit late.')
        await clickLink('#tuja_save_button')
        await expectSuccessMessage('Ändringarna har sparats.')

        await goto(groupProps.portalUrl)
        await expectPageTitle(`Hej ${newName}`)

        await goto(`http://localhost:8080/${groupProps.key}/andra`)
        expect(await $eval('input[name="tuja-group__age"]', node => node.checked)).toBeTruthy()
        await expectFormValue('#tuja-group__name', newName)
        await expectFormValue('#tuja-group__note', 'We will arrive a bit late.')
      })

      it.each([
        ['8311090123', '19831109-0123'],
        ['831109-0123', '19831109-0123'],
        ['198311090123', '19831109-0123'],
        ['19831109-0123', '19831109-0123'],
        ['831109', '19831109-0000'],
        ['83-11-09', '19831109-0000'],
        ['63-11-09', '19631109-0000'],
        ['73-11-09', '19731109-0000'],
        ['03-11-09', '20031109-0000'],
        ['13-11-09', '20131109-0000'],
        ['19831109', '19831109-0000'],
        ['1983-11-09', '19831109-0000'],
        ['198311090000', '19831109-0000'],
        ['8311090000', '19831109-0000'],
        ['1983-11-09--0123', '19831109-0123']
      ])('should accept valid PNO %s', async (input, expected) => {
        await goto(`http://localhost:8080/${groupProps.key}/andra-personer`)

        await type('div.tuja-person-role-group_leader input[name^="tuja-person__pno__"]', input)
        await clickLink('button[name="tuja-action"]')
        await expectSuccessMessage('Ändringarna har sparats.')

        await goto(`http://localhost:8080/${groupProps.key}/andra-personer`)
        await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__pno__"]', expected)
      })

      it.each([
        ['19831109-012', true],
        ['19831109-01', true],
        ['12345', true],
        ['198300000000', true],
        ['8300000000', true],
        ['830000000000', true],
        ['1234567890', true],
        ['nej', true],
        ['19909999-0000', true],
        ['19830230-0000', false]
      ])('should reject invalid PNO %s', async (input, isClientSideFailure) => {
        await goto(`http://localhost:8080/${groupProps.key}/anmal-mig`)

        await type('input[name^="tuja-person__name"]', 'Alice')
        await type('input[name^="tuja-person__pno"]', input)
        if (isClientSideFailure) {
          await click('button[name="tuja-action"]')
          await page.waitFor(500)
          const isInvalid = await $eval('input[name^="tuja-person__pno"]', el => el.matches(`:invalid`))
          expect(isInvalid).toBeTruthy()
        } else {
          await clickLink('button[name="tuja-action"]')

          await expectErrorMessage('Ogiltigt datum eller personnummer')
        }
      })

      it.each([
        [
          '  David Dawson  ',
          'David Dawson',
          '83-01-01',
          '19830101-0000',
          'Vegan   ',
          'Vegan'
        ],
        [
          'Emily Emilia Edvina Ellison',
          'Emily Emilia Edvina Ellison',
          '830131-1234',
          '19830131-1234',
          '',
          ''
        ],
        [
          'B',
          'B',
          '101010',
          '20101010-0000',
          '    .',
          '.'
        ]
      ])('should be possible to sign up as new team member "%s"', async (nameInput, nameExpected, pnoInput, pnoExpected, foodInput, foodExpected) => {
        await goto(`http://localhost:8080/${groupProps.key}/anmal-mig`)

        await type('input#tuja-person__name', nameInput)
        await type('input#tuja-person__pno', pnoInput)
        await type('input#tuja-person__food', foodInput)

        // Members of competing teams should not be able to add a note. The team-level note field and the person-level food field should be enough.
        await expectElementCount('input#tuja-person__note', 0)

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Tack')

        const editPersonUrl = await $eval('#tuja_signup_success_edit_link', node => node.href)
        await goto(editPersonUrl)

        await expectFormValue('input#tuja-person__name', nameExpected)
        await expectFormValue('input#tuja-person__pno', pnoExpected)
        await expectFormValue('input#tuja-person__food', foodExpected)
        await expectElementCount('input#tuja-person__note', 0)
      })

      it('should be possible to sign up and later change registration', async () => {

        //
        // Signing up
        //

        let name = 'Alice'
        let pno = '19840101-0000'
        let food = ''
        await goto(`http://localhost:8080/${groupProps.key}/anmal-mig`)

        await type('input#tuja-person__name', name)
        await type('input#tuja-person__pno', pno)
        await type('input#tuja-person__food', food)
        await expectElementCount('input#tuja-person__note', 0)

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Tack')

        const editPersonUrl = await $eval('#tuja_signup_success_edit_link', node => node.href)

        //
        // Editing registration first time
        //

        await goto(editPersonUrl)
        await expectFormValue('input#tuja-person__name', name)
        await expectFormValue('input#tuja-person__pno', pno)
        await expectFormValue('input#tuja-person__food', food)
        await expectElementCount('input#tuja-person__note', 0)

        name = 'Alicia'
        pno = '19840202-0000'
        food = 'Allergic to gluten'

        await type('input#tuja-person__name', name)
        await type('input#tuja-person__pno', pno)
        await type('input#tuja-person__food', food)

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Ändringarna har sparats. Tack.')

        await goto(editPersonUrl)
        await expectFormValue('input#tuja-person__name', name)
        await expectFormValue('input#tuja-person__pno', pno)
        await expectFormValue('input#tuja-person__food', food)
        await expectElementCount('input#tuja-person__note', 0)

        //
        // Editing registration second time
        //

        name = 'Allison'
        pno = '19840303-0000'
        food = 'Allergic to lactose'

        await goto(editPersonUrl)
        await type('input#tuja-person__name', name)
        await type('input#tuja-person__pno', pno)
        await type('input#tuja-person__food', food)
        await expectElementCount('input#tuja-person__note', 0)

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Ändringarna har sparats. Tack.')

        await goto(editPersonUrl)

        await expectFormValue('input#tuja-person__name', name)
        await expectFormValue('input#tuja-person__pno', pno)
        await expectFormValue('input#tuja-person__food', food)
        await expectElementCount('input#tuja-person__note', 0)
      })

      it('should evaluate registration rules for team size', async () => {
        const tempGroupProps = await defaultPage.signUpTeam(adminPage)

        await goto(`http://localhost:8080/${tempGroupProps.key}/andra-personer`)

        const saveAndVerify = async (isTeamSizeWarningExpected) => {
          await clickLink('button[name="tuja-action"]')
          await expectSuccessMessage('Ändringarna har sparats.')

          await goto(`http://localhost:8080/${tempGroupProps.key}`)
          if (isTeamSizeWarningExpected) {
            await expectWarningMessage('Anmälan är inte riktigt komplett.')
          } else {
            await expectElementCount('.tuja-message-warning', 0)
          }

          await goto(`http://localhost:8080/${tempGroupProps.key}/status`)
          if (isTeamSizeWarningExpected) {
            await expectElementCount('.tuja-group-status-blocker-message', 1)
            await expectElementCount('.tuja-group-status-warning-message', 0)
          } else {
            await expectElementCount('.tuja-group-status-blocker-message', 0)
            await expectElementCount('.tuja-group-status-warning-message', 0)
          }

          await goto(`http://localhost:8080/${tempGroupProps.key}/andra-personer`)
        }

        const addCompetingTeamMember = async (name, birthDate) => {
          await click('div.tuja-person-role-regular_group_member button.tuja-add-person')
          await type('div.tuja-person-role-regular_group_member div.tuja-signup-person:last-child input[name^="tuja-person__name__"]', name)
          await type('div.tuja-person-role-regular_group_member div.tuja-signup-person:last-child input[name^="tuja-person__pno__"]', birthDate)
        }

        // Add two team members (and verify that a warning is shown about TOO FEW competing team members, and that the group sign-up status is INCOMPLETE)
        await addCompetingTeamMember('Bob', '20001010-1234')
        await addCompetingTeamMember('Carol', '20011011-1234')
        await saveAndVerify(true)

        // Add one extra contact (and verify that a warning is still shown about too few competing team members, and that the group sign-up status is INCOMPLETE)
        await click('div.tuja-person-role-extra_contact button.tuja-add-person')
        await type('div.tuja-person-role-extra_contact input[name^="tuja-person__email__"]', 'extra-contact@example.com')
        await saveAndVerify(true)

        // Add one team member (and verify that a warning is no longer shown, and that the group sign-up status is ACCEPTED)
        await addCompetingTeamMember('Dave', '20021012-1234')
        await saveAndVerify(false)

        // Add five team members (and verify that warning about TOO MANY competing team members, and that the group sign-up status is INCOMPLETE)
        await addCompetingTeamMember('Emily', '20031013-1234')
        await addCompetingTeamMember('Fred', '20041014-1234')
        await addCompetingTeamMember('Grace', '20051015-1234')
        await addCompetingTeamMember('Henry', '20061016-1234')
        await addCompetingTeamMember('Isabella', '20071017-1234')
        await saveAndVerify(true)

        // Remove one team member (and verify that a warning is no longer shown, and that the group sign-up status is ACCEPTED)
        await click('div.tuja-person-role-regular_group_member div.tuja-signup-person:last-child button.tuja-delete-person')
        await saveAndVerify(false)
      })

      it('should be possible to sign up as administrator', async () => {
        const tempGroupProps = await defaultPage.signUpTeam(adminPage, true, false)

        await goto(`http://localhost:8080/${tempGroupProps.key}/andra-personer`)

        await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectFormValue('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__email__"]', 'amber@example.com')
      })

      it('should be possible to edit team members', async () => {
        const tempGroupProps = await defaultPage.signUpTeam(adminPage)

        await goto(tempGroupProps.portalUrl)
        await clickLink('#tuja_edit_people_link')

        //
        // Verify data from when the team was registered
        //

        await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 0)

        await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__name__"]', 'Amber')
        await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__email__"]', 'amber@example.com')
        await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__phone__"]', '+4670123456')

        //
        // Change team leader
        //

        await type('div.tuja-person-role-group_leader input[name^="tuja-person__name__"]', 'Alice')
        await type('div.tuja-person-role-group_leader input[name^="tuja-person__pno__"]', '1980-01-02')
        await type('div.tuja-person-role-group_leader input[name^="tuja-person__food__"]', 'Vegan')

        //
        // Add two new team members
        //

        await click('div.tuja-person-role-regular_group_member button.tuja-add-person')
        await click('div.tuja-person-role-regular_group_member button.tuja-add-person')
        await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 2)
        await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__name__"]', 2)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__email__"]', 0)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__phone__"]', 0)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__pno__"]', 2)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__food__"]', 2)

        await type('div.tuja-person-role-regular_group_member div.tuja-signup-person:nth-child(1) input[name^="tuja-person__name__"]', 'Bob')
        await type('div.tuja-person-role-regular_group_member div.tuja-signup-person:nth-child(1) input[name^="tuja-person__pno__"]', '1979-12-31')
        await type('div.tuja-person-role-regular_group_member div.tuja-signup-person:nth-child(2) input[name^="tuja-person__name__"]', 'Dave')
        await type('div.tuja-person-role-regular_group_member div.tuja-signup-person:nth-child(2) input[name^="tuja-person__pno__"]', '1990-08-08')

        //
        // Add extra contact
        //

        await click('div.tuja-person-role-extra_contact button.tuja-add-person')
        await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 2)
        await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__name__"]', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__email__"]', 1)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__phone__"]', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__pno__"]', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__food__"]', 0)

        await type('div.tuja-person-role-extra_contact input[name^="tuja-person__email__"]', 'extra-contact@example.com')

        //
        // Save changes
        //

        await clickLink('button[name="tuja-action"]')
        await expectSuccessMessage('Ändringarna har sparats.')

        //
        // Reload page without re-posting data (just in case the form shows data from $_POST rather than actual database values)
        //
        await goto(`http://localhost:8080/${tempGroupProps.key}/andra-personer`)

        //
        // Verify data when reloading the page
        //

        await expectFormValue('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__name__"]', 'Alice')
        await expectFormValue('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__pno__"]', '19800102-0000')
        await expectFormValue('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__food__"]', 'Vegan')
        await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person:nth-child(1) input[name^="tuja-person__name__"]', 'Bob')
        await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person:nth-child(1) input[name^="tuja-person__pno__"]', '19791231-0000')
        await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person:nth-child(2) input[name^="tuja-person__name__"]', 'Dave')
        await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person:nth-child(2) input[name^="tuja-person__pno__"]', '19900808-0000')
        await expectFormValue('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__email__"]', 'extra-contact@example.com')

        //
        // Delete Bob
        //

        await click('div.tuja-person-role-regular_group_member div.tuja-signup-person:nth-child(1) button.tuja-delete-person')
        await clickLink('button[name="tuja-action"]')
        await expectSuccessMessage('Ändringarna har sparats.')

        //
        // Reload page without re-posting data (just in case the form shows data from $_POST rather than actual database values)
        //
        await goto(`http://localhost:8080/${tempGroupProps.key}/andra-personer`)

        //
        // Verify that Dave is now the one and only regular team member
        //

        await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person:nth-child(1) input[name^="tuja-person__name__"]', 'Dave')
        await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person:nth-child(1) input[name^="tuja-person__pno__"]', '19900808-0000')
      })
    })
  })

  describe('Checking in', () => {
    it('basic tests', async () => {
      const tempGroupProps = await defaultPage.signUpTeam(adminPage)

      // cannot check in unless status is AWAITING_CHECKIN
      await goto(`http://localhost:8080/${tempGroupProps.key}/incheckning`)
      await expectErrorMessage('Incheckningen är inte öppen för ert lag.')
      await expectElementCount('button', 0)
      await expectElementCount('input', 0)

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Group&tuja_competition=${competitionId}&tuja_group=${tempGroupProps.id}`)
      await adminPage.clickLink('button[name="tuja_points_action"][value="transition__accepted"]')
      await adminPage.clickLink('button[name="tuja_points_action"][value="transition__awaiting_checkin"]')

      // message shown when team answers NO
      await goto(`http://localhost:8080/${tempGroupProps.key}/incheckning`)
      await click('#tuja-checkin-answer-1')
      await clickLink('button[name="tuja-action"]')
      await expectInfoMessage('Okej, då behöver ni gå till Kundtjänst.')
      await expectElementCount('button', 0)
      await expectElementCount('input', 0)

      // message shown when team answers YES
      await goto(`http://localhost:8080/${tempGroupProps.key}/incheckning`)
      await click('#tuja-checkin-answer-0')
      await clickLink('button[name="tuja-action"]')
      await expectSuccessMessage('Tack, ni är nu incheckade.')
      await expectElementCount('button', 0)
      await expectElementCount('input', 0)

      // cannot check in when status is CHECKEDIN
      await goto(`http://localhost:8080/${tempGroupProps.key}/incheckning`)
      await expectSuccessMessage('Ni är redan incheckade.')
      await expectElementCount('button', 0)
      await expectElementCount('input', 0)
    })
  })

  describe('Deleting (unregistering) teams', () => {

    describe('as admin', () => {
      let groupProps = null

      beforeAll(async () => {
        groupProps = await defaultPage.signUpTeam(adminPage, true)
        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Group&tuja_competition=${competitionId}&tuja_group=${groupProps.id}`)
        await adminPage.clickLink('button[name="tuja_points_action"][value="transition__deleted"]')
      })

      it.each([
        '',
        'andra',
        'andra-personer',
        'biljetter',
        'anmal-mig',
      ])('should NOT be possible to do anything on team page /%s', async (urlSuffix) => {
        await goto(`http://localhost:8080/${groupProps.key}/${urlSuffix}`)

        await expectErrorMessage('Laget är avanmält.')

        await expectElementCount('div.entry-content p > a', 0) // No links shown
        await expectElementCount('div.entry-content form', 0) // No forms shown
        await expectElementCount('div.entry-content button', 0) // No buttons shown
      })
    })

    describe('as user', () => {
      let groupProps = null

      beforeAll(async () => {
        groupProps = await defaultPage.signUpTeam(adminPage, true)
        await defaultPage.goto(groupProps.portalUrl)
        await defaultPage.clickLink('#tuja_unregister_team_link')
        await defaultPage.clickLink('button[name="tuja-action"][value="cancel"]')
        await expectSuccessMessage('Ni är nu avanmälda')
      })

      it.each([
        '',
        'andra',
        'andra-personer',
        'biljetter',
        'anmal-mig',
      ])('should NOT be possible to do anything on team page /%s', async (urlSuffix) => {
        await goto(`http://localhost:8080/${groupProps.key}/${urlSuffix}`)

        await expectErrorMessage('Laget är avanmält.')

        await expectElementCount('div.entry-content p > a', 0) // No links shown
        await expectElementCount('div.entry-content form', 0) // No forms shown
        await expectElementCount('div.entry-content button', 0) // No buttons shown
      })

    })
  })

})
