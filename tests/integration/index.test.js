const querystring = require('querystring')
const puppeteer = require('puppeteer')
const faker = require('faker')

const click = async (selector) => {
  await page.click(selector)
}

const type = async (selector, value) => {
  const el = await page.$(selector)
  await el.evaluate(el => el.value = '')
  await el.type(value)
}

const goto = async (url) => {
  await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 10000 }),
      page.goto(url)
    ]
  )
}

const clickLink = async (selector) => {
  await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 10000 }),
      page.click(selector)
    ]
  )
}

const expectSuccessMessage = async (expected) => {
  const actual = await page.$eval('p.tuja-message-success', node => node.innerText)
  await expect(actual).toContain(expected)
}

const expectErrorMessage = async (expected) => {
  const actual = await page.$eval('.tuja-message-error', node => node.innerText)
  await expect(actual).toContain(expected)
}

const expectFormValue = async (selector, expected) => {
  const actual = await page.$eval(selector, node => node.value)
  await expect(actual).toBe(expected)
}

const expectPageTitle = async (expected) => {
  expect(await page.title()).toContain(expected)
}

const expectElementCount = async (selector, expectedCount) => {
  expect(await page.$$(selector)).toHaveLength(expectedCount)
}

const asAdmin = async (closure) => {
  // Log in to Admin console
  await goto('http://localhost:8080/wp-admin')
  await type('#user_login', 'admin')
  await type('#user_pass', 'admin')
  await clickLink('#wp-submit')

  await closure()

  await goto(`http://localhost:8080/wp-login.php?action=logout`)
  const logoutLink = await page.$('a')
  await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
      logoutLink.click()
    ]
  )
}

describe('wp-tuja', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null

  beforeAll(async () => {

    jest.setTimeout(300000)

    await asAdmin(async () => {
      // Go to Tuja page in Admin console
      await goto('http://localhost:8080/wp-admin/admin.php?page=tuja')

      // Create new competition
      competitionName = 'Test Competition ' + new Date().getTime()

      await type('#tuja_competition_name', competitionName)
      await clickLink('#tuja_create_competition_button')

      const links = await page.$$('form.tuja a')
      let link = null
      for (let i = 0; i < links.length; i++) {
        const el = links[i]
        const linkText = await el.evaluate(node => node.innerText)
        const isEqual = linkText === competitionName
        if (isEqual) {
          link = el
          break
        }
      }
      const [resp] = await Promise.all([
          page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          link.click()
        ]
      )

      competitionId = querystring.parse(resp.url()).tuja_competition
      await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Shortcodes&tuja_competition=${competitionId}`)

      const signUpLink = await page.$('#tuja_shortcodes_competitionsignup_link')
      const signUpLinkUrl = await signUpLink.evaluate(node => node.href)

      competitionKey = signUpLinkUrl.split(/\//)[3]

      // console.log('🦋', competitionId, competitionKey)

      await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

      await click('#tuja_tab_groups')

      await click('#tuja_competition_settings_initial_group_status-accepted')

      const addGroupCategory = async (name, isCrew, ruleSetName) => {
        await click('#tuja_add_group_category_button')
        const groupCategoryForm = await page.$('div.tuja-groupcategory-form:last-of-type')
        const groupCategoryName = await groupCategoryForm.$('input[type=text]')
        const groupCategoryNameFieldName = await groupCategoryName.evaluate(node => node.name)
        const tempGroupCategoryId = groupCategoryNameFieldName.split(/__/)[2]
        await groupCategoryName.type(name)
        const groupCategoryRules = await groupCategoryForm.$('select[name="groupcategory__ruleset__' + tempGroupCategoryId + '"]')
        await groupCategoryRules.select(ruleSetName)
        await page.click('input[name="groupcategory__iscrew__' + tempGroupCategoryId + '"][value="' + isCrew + '"]')
      }

      await addGroupCategory('Young Participants', false, 'tuja\\util\\rules\\YoungParticipantsRuleSet')
      await addGroupCategory('Old Participants', false, 'tuja\\util\\rules\\OlderParticipantsRuleSet')
      await addGroupCategory('The Crew', true, 'tuja\\util\\rules\\CrewMembersRuleSet')

      await clickLink('#tuja_save_competition_settings_button')
    })
  })

  describe('Signing up as new team', () => {

    const signUpTeam = async () => {
      const name = faker.lorem.words()
      await goto(`http://localhost:8080/${competitionKey}/anmal`)
      await expectPageTitle(`Anmäl er till ${competitionName}`)
      const teamLeader = faker.helpers.contextualCard()
      await click('#tuja-group__age-0')
      await type('#tuja-group__name', name)
      await type('#tuja-person__name', teamLeader.name)
      await type('#tuja-person__email', teamLeader.email)
      await type('#tuja-person__phone', '070-123456')
      await type('#tuja-person__pno', '1980-01-01')
      await clickLink('#tuja_signup_button')
      // await page.screenshot({ path: 'screenshot.png', fullPage: true })
      await expectSuccessMessage('Tack')
      const groupPortalLinkNode = await page.$('#tuja_signup_success_edit_link')
      const portalUrl = await groupPortalLinkNode.evaluate(node => node.href)
      const key = await groupPortalLinkNode.evaluate(node => node.dataset.groupKey)
      return { key, portalUrl, name, teamLeader }
    }

    let groupProps = null

    beforeAll(async () => {
      // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
      await page.emulate(puppeteer.devices['iPhone 6 Plus'])

      groupProps = await signUpTeam()
    })

    it('should give a link for editing', async () => {
      expect(groupProps.portalUrl).toBe(`http://localhost:8080/${groupProps.key}`)
      await goto(groupProps.portalUrl)
      await expectPageTitle(`Hej ${groupProps.name}`)
    })

    it('should be possible to change name and category', async () => {
      const newName = `New and improved ${groupProps.name}`

      await goto(groupProps.portalUrl)
      await clickLink('#tuja_edit_group_link')
      await click('#tuja-group__age-1')
      await type('#tuja-group__name', newName)
      await clickLink('#tuja_save_button')
      await expectSuccessMessage('Ändringarna har sparats.')

      await goto(groupProps.portalUrl)
      await expectPageTitle(`Hej ${newName}`)
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
        const isInvalid = await page.$eval('input[name^="tuja-person__pno"]', el => el.matches(`:invalid`))
        expect(isInvalid).toBeTruthy()
      } else {
        await clickLink('button[name="tuja-action"]')

        await expectErrorMessage('Ogiltigt datum eller personnummer')
      }
    })

    it('should be possible to edit team members', async () => {
      const newName = `New and improved ${groupProps.name}`

      await goto(groupProps.portalUrl)
      await clickLink('#tuja_edit_people_link')

      await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
      await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__name__"]', groupProps.teamLeader.name)
      await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__email__"]', groupProps.teamLeader.email)
      await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__phone__"]', '+4670123456')

      await type('div.tuja-person-role-group_leader input[name^="tuja-person__name__"]', 'Alice')
      await type('div.tuja-person-role-group_leader input[name^="tuja-person__pno__"]', '1980-01-02')
      await type('div.tuja-person-role-group_leader input[name^="tuja-person__food__"]', 'Vegan')

      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 0)
      await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 0)

      await click('div.tuja-person-role-regular_group_member button.tuja-add-person')
      await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 1)
      await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 0)

      await type('div.tuja-person-role-regular_group_member input[name^="tuja-person__name__"]', 'Bob')
      await type('div.tuja-person-role-regular_group_member input[name^="tuja-person__pno__"]', '1979-12-31')
      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__name__"]', 1)
      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__email__"]', 0)
      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__phone__"]', 0)
      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__pno__"]', 1)
      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__food__"]', 1)

      await click('div.tuja-person-role-extra_contact button.tuja-add-person')
      await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
      await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 1)
      await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 1)

      await type('div.tuja-person-role-extra_contact input[name^="tuja-person__email__"]', 'extra-contact@example.com')
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__name__"]', 0)
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__email__"]', 1)
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__phone__"]', 0)
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__pno__"]', 0)
      await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__food__"]', 0)

      await clickLink('button[name="tuja-action"]')
      await expectSuccessMessage('Ändringarna har sparats.')

      await expectFormValue('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__name__"]', 'Alice')
      await expectFormValue('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__pno__"]', '1980-01-02') // TODO: Why is this not formatted?
      await expectFormValue('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__food__"]', 'Vegan')
      await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__name__"]', 'Bob')
      await expectFormValue('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__pno__"]', '19791231-0000')
      await expectFormValue('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person input[name^="tuja-person__email__"]', 'extra-contact@example.com')
    })
  })

  describe('Crew', () => {

    let groupProps = null

    beforeAll(async () => {
      await asAdmin(async () => {
        // Go to admin console
        await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Groups&tuja_competition=${competitionId}`)

        // Select crew category in tuja_new_group_type
        await page.screenshot({ path: 'screenshot.png', fullPage: true })
        const id = await page.$eval('select[name="tuja_new_group_type"] > option:last-child', node => node.value)
        await page.select('select[name="tuja_new_group_type"]', id)

        // Type crew group name in tuja_new_group_name
        await type('input[name="tuja_new_group_name"]', '_ The Regular Crew') // Underscore added to ensure group shown first in list(s)

        // Click correct tuja_action button
        await clickLink('button[name="tuja_action"][value="group_create"]')

        // Wait for page to load and extract crew group id from link in group list
        const groupTableRow = await page.$('table#tuja_groups_list > tbody > tr:first-child > td:first-child')
        const key = await groupTableRow.evaluate(node => node.dataset.groupKey)
        groupProps = {
          key
        }
      })
    })

    it('should be possible with good data', async () => {
      // TODO: More positive test cases
      await goto(`http://localhost:8080/${groupProps.key}/anmal-mig`)

      await type('input#tuja-person__name', 'Carol')
      await type('input#tuja-person__email', 'carol@example.com')
      await type('input#tuja-person__phone', '070-12345678')
      await type('input#tuja-person__food', 'Picky about eggs')

      await clickLink('button[name="tuja-action"]')

      await expectSuccessMessage('Tack')

      const editPersonUrl = await page.$eval('#tuja_signup_success_edit_link', node => node.href)
      await goto(editPersonUrl)

      await expectFormValue('input#tuja-person__name', 'Carol')
      await expectFormValue('input#tuja-person__email', 'carol@example.com')
      await expectFormValue('input#tuja-person__phone', '+467012345678')
      await expectFormValue('input#tuja-person__food', 'Picky about eggs')
    })
    it.each([
      ['Trudy', '', '070-1234567', 'No fondness for spam', 'E-postadressen ser konstig ut'] // Missing required field
      // TODO: More negative test cases
    ])('should be not be possible with bad data', async (name, email, phone, food, expectedErrorMessage) => {
      await goto(`http://localhost:8080/${groupProps.key}/anmal-mig`)

      await type('input[name^="tuja-person__name"]', name)
      await type('input[name^="tuja-person__email"]', email)
      await type('input[name^="tuja-person__phone"]', phone)
      await type('input[name^="tuja-person__food"]', food)

      await clickLink('button[name="tuja-action"]')

      await expectErrorMessage(expectedErrorMessage)
    })
  })
})
