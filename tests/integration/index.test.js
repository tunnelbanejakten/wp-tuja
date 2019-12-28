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
  console.log('➡️ Navigating to ', url)
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

const expectToContain = async (selector, expected) => {
  const actual = await page.$eval(selector, node => node.innerText)
  await expect(actual).toContain(expected)
}
const expectSuccessMessage = async (expected) => expectToContain('.tuja-message-success', expected)

const expectWarningMessage = async (expected) => expectToContain('.tuja-message-warning', expected)

const expectErrorMessage = async (expected) => expectToContain('.tuja-message-error', expected)

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
  // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
  await page.emulate(puppeteer.devices['iPad Pro landscape'])
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
  // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
  await page.emulate(puppeteer.devices['iPhone 6 Plus'])
}

const takeScreenshot = async () => {
  await page.screenshot({ path: `screenshot-${new Date().getTime()}.png`, fullPage: true })
}

describe('wp-tuja', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null

  const signUpTeam = async (isAutomaticallyAccepted = true) => {
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
    if (isAutomaticallyAccepted) {
      await expectToContain('#tuja_signup_button', 'Anmäl lag')
    } else {
      await expectToContain('#tuja_signup_button', 'Anmäl lag till väntelista')
      await expectWarningMessage('Varför väntelista?')
    }
    await clickLink('#tuja_signup_button')
    if (isAutomaticallyAccepted) {
      await expectSuccessMessage('Tack')
    } else {
      await expectWarningMessage('Ert lag står på väntelistan')
    }
    await takeScreenshot()
    const groupPortalLinkNode = await page.$('#tuja_group_home_link')
    const portalUrl = await groupPortalLinkNode.evaluate(node => node.href)
    const key = await groupPortalLinkNode.evaluate(node => node.dataset.groupKey)
    const id = await groupPortalLinkNode.evaluate(node => node.dataset.groupId)
    return { key, id, portalUrl, name, teamLeader }
  }

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

  describe('Tickets', () => {

    let stationsProps = null

    beforeAll(async () => {
      // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
      await page.emulate(puppeteer.devices['iPhone 6 Plus'])

      await asAdmin(async () => {

        //
        // Configure stations
        //

        await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Stations&tuja_competition=${competitionId}`)

        await type('#tuja_station_name', 'Hornstull')
        await clickLink('#tuja_station_create_button')

        await type('#tuja_station_name', 'Slussen')
        await clickLink('#tuja_station_create_button')

        await type('#tuja_station_name', 'Mariatorget')
        await clickLink('#tuja_station_create_button')

        await type('#tuja_station_name', 'Skanstull')
        await clickLink('#tuja_station_create_button')

        //
        // Configure ticketing
        //

        stationsProps = await page.$$eval('form.tuja a[data-key]', nodes => nodes.map(node => ({
          id: node.dataset.id,
          key: node.dataset.key,
          name: node.textContent
        })))

        stationsProps[0].word = 'BLUEBERRY'
        stationsProps[1].word = 'RASPBERRY'
        stationsProps[2].word = 'STRAWBERRY'
        stationsProps[3].word = 'CLOUDBERRY'
        stationsProps[0].colour = '#b7fda0'
        stationsProps[1].colour = '#fdd9a8'
        stationsProps[2].colour = '#b0eefd'
        stationsProps[3].colour = '#fdbcc0'
        stationsProps[0].password = 'treasure'
        stationsProps[1].password = 'gold'
        stationsProps[2].password = 'loot'
        stationsProps[3].password = 'winnings'

        await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=StationsTicketing&tuja_competition=${competitionId}`)

        await page.$eval(`input[name="tuja_ticketdesign__${stationsProps[0].id}__colour"]`, (node, color) => node.value = color, stationsProps[0].colour)
        await page.$eval(`input[name="tuja_ticketdesign__${stationsProps[1].id}__colour"]`, (node, color) => node.value = color, stationsProps[1].colour)
        await page.$eval(`input[name="tuja_ticketdesign__${stationsProps[2].id}__colour"]`, (node, color) => node.value = color, stationsProps[2].colour)
        await page.$eval(`input[name="tuja_ticketdesign__${stationsProps[3].id}__colour"]`, (node, color) => node.value = color, stationsProps[3].colour)
        await type(`input[name="tuja_ticketdesign__${stationsProps[0].id}__word"]`, stationsProps[0].word)
        await type(`input[name="tuja_ticketdesign__${stationsProps[1].id}__word"]`, stationsProps[1].word)
        await type(`input[name="tuja_ticketdesign__${stationsProps[2].id}__word"]`, stationsProps[2].word)
        await type(`input[name="tuja_ticketdesign__${stationsProps[3].id}__word"]`, stationsProps[3].word)
        await type(`input[name="tuja_ticketdesign__${stationsProps[0].id}__password"]`, stationsProps[0].password)
        await type(`input[name="tuja_ticketdesign__${stationsProps[1].id}__password"]`, stationsProps[1].password)
        await type(`input[name="tuja_ticketdesign__${stationsProps[2].id}__password"]`, stationsProps[2].password)
        await type(`input[name="tuja_ticketdesign__${stationsProps[3].id}__password"]`, stationsProps[3].password)

        await type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[1].id}"]`, '1')
        await type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[2].id}"]`, '2')
        await type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[3].id}"]`, '3')
        await type(`input[name="tuja_ticketcouponweight__${stationsProps[1].id}__${stationsProps[2].id}"]`, '4')
        await type(`input[name="tuja_ticketcouponweight__${stationsProps[1].id}__${stationsProps[3].id}"]`, '5')
        await type(`input[name="tuja_ticketcouponweight__${stationsProps[2].id}__${stationsProps[3].id}"]`, '6')

        await clickLink('button[name="tuja_action"][value="save"]')
      })
    })

    it('get tickets', async () => {
      const groupAliceProps = await signUpTeam()
      const groupBobProps = await signUpTeam()

      //
      // Verify that Alice's team has no tickets.
      //

      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 0)

      //
      // Verify that Bob's team has no tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 0)

      //
      // Bob's team uses their first password and gets their first two tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[0].password)
      await clickLink('#tuja_validate_ticket_button')

      await expectSuccessMessage('Ni har fått 2 nya biljetter.')
      await expectElementCount('div.tuja-ticket', 2)
      const initialTicketWordsForTeamBob = await page.$$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(initialTicketWordsForTeamBob).toHaveLength(2)
      expect(initialTicketWordsForTeamBob.includes(stationsProps[0].word)).toBeFalsy()
      expect(initialTicketWordsForTeamBob.includes(stationsProps[1].word)).toBeTruthy()
      expect(initialTicketWordsForTeamBob.includes(stationsProps[2].word) ? !initialTicketWordsForTeamBob.includes(stationsProps[3].word) : initialTicketWordsForTeamBob.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Bob's team tried to cheat and use their first password once more.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[0].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectErrorMessage(`Cannot use ${stationsProps[0].password} twice`)
      await expectElementCount('div.tuja-ticket', 2)
      const ticketWordsAfterRetry = await page.$$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(ticketWordsAfterRetry).toEqual(initialTicketWordsForTeamBob)

      //
      // Alice's team uses the same code as Bob's team used (once successfully and once to cheat)
      //

      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 0)
      await type('#tuja_ticket_password', stationsProps[0].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectSuccessMessage('Ni har fått 2 nya biljetter.')
      await expectElementCount('div.tuja-ticket', 2)
      const initialTicketWordsForTeamAlice = await page.$$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(initialTicketWordsForTeamAlice).toHaveLength(2)
      expect(initialTicketWordsForTeamAlice.includes(stationsProps[0].word)).toBeFalsy()
      expect(initialTicketWordsForTeamAlice.includes(stationsProps[1].word)).toBeTruthy()
      expect(initialTicketWordsForTeamAlice.includes(stationsProps[2].word) ? !initialTicketWordsForTeamAlice.includes(stationsProps[3].word) : initialTicketWordsForTeamAlice.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Bob's team uses their second password and gets the final two tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[1].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectSuccessMessage('Ni har fått 2 nya biljetter.')
      const finalTicketWords = await page.$$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(finalTicketWords).toHaveLength(4)
      expect(finalTicketWords.includes(stationsProps[0].word)).toBeTruthy()
      expect(finalTicketWords.includes(stationsProps[1].word)).toBeTruthy()
      expect(finalTicketWords.includes(stationsProps[2].word)).toBeTruthy()
      expect(finalTicketWords.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Bob's team uses their third password but cannot get any more tickets.
      //

      await goto(`http://localhost:8080/${groupBobProps.key}/biljetter`)
      await type('#tuja_ticket_password', stationsProps[2].password)
      await clickLink('#tuja_validate_ticket_button')
      await expectSuccessMessage('Ni har fått 0 nya biljetter.')
      const finalRetryTicketWords = await page.$$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
      expect(finalRetryTicketWords).toHaveLength(4)
      expect(finalRetryTicketWords.includes(stationsProps[0].word)).toBeTruthy()
      expect(finalRetryTicketWords.includes(stationsProps[1].word)).toBeTruthy()
      expect(finalRetryTicketWords.includes(stationsProps[2].word)).toBeTruthy()
      expect(finalRetryTicketWords.includes(stationsProps[3].word)).toBeTruthy()

      //
      // Verify that Alice's team (still) has two tickets.
      //

      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)
      await expectElementCount('div.tuja-ticket', 2)
    })

    it('should not care about capitalization of password', async () => {
      const groupAliceProps = await signUpTeam()
      await goto(`http://localhost:8080/${groupAliceProps.key}/biljetter`)

      //
      // Start by verifying that invalid passwords are rejected with an error message
      //

      const invalidPassword = 'invalid' + stationsProps[0].password
      await type('#tuja_ticket_password', invalidPassword)
      await clickLink('#tuja_validate_ticket_button')
      await expectErrorMessage(`The password ${invalidPassword} is not correct`)

      //
      // Try a couple of valid passwords
      //

      const validPasswords = [
        'TREAsure',
        '  gold',
        'LOOT  '
      ]
      for (const password of validPasswords) {
        await type('#tuja_ticket_password', password)
        await clickLink('#tuja_validate_ticket_button')

        await expectSuccessMessage('Ni har fått') // We don't care about the number of tickets, just that we don't get an error
      }
    })
  })

  describe('Signing up as new team', () => {

    beforeAll(async () => {
      // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
      await page.emulate(puppeteer.devices['iPhone 6 Plus'])

    })

    describe('when teams need to be approved', () => {

      let groupProps = null

      beforeAll(async () => {
        await asAdmin(async () => {
          await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

          await click('#tuja_tab_groups')

          await click('#tuja_competition_settings_initial_group_status-awaiting_approval')

          await clickLink('#tuja_save_competition_settings_button')
        })
        groupProps = await signUpTeam(false)
      })

      it('the team portal becomes available after team has been accepted', async () => {
        const toBeAcceptedGroup = await signUpTeam(false)

        await goto(toBeAcceptedGroup.portalUrl)
        await expectWarningMessage('Ert lag står på väntelistan')
        await expectElementCount('div.entry-content p > a', 0) // No links shown
        await expectElementCount('div.entry-content p.tuja-message-success', 0)
        await expectElementCount('div.entry-content p.tuja-message-warning', 1)
        await expectElementCount('div.entry-content p.tuja-message-error', 0)

        await asAdmin(async () => {
          await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Group&tuja_competition=${competitionId}&tuja_group=${toBeAcceptedGroup.id}`)

          await clickLink('button[name="tuja_points_action"][value="transition__accepted"]')
        })

        await goto(toBeAcceptedGroup.portalUrl)
        await expectElementCount('div.entry-content p > a', 3) // No links shown
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
        await asAdmin(async () => {
          await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

          await click('#tuja_tab_groups')

          await click('#tuja_competition_settings_initial_group_status-accepted')

          await clickLink('#tuja_save_competition_settings_button')
        })
        groupProps = await signUpTeam()
      })

      it('team portal is accessible', async () => {
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

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Tack')

        const editPersonUrl = await page.$eval('#tuja_signup_success_edit_link', node => node.href)
        await goto(editPersonUrl)

        await expectFormValue('input#tuja-person__name', nameExpected)
        await expectFormValue('input#tuja-person__pno', pnoExpected)
        await expectFormValue('input#tuja-person__food', foodExpected)
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

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Tack')

        const editPersonUrl = await page.$eval('#tuja_signup_success_edit_link', node => node.href)

        //
        // Editing registration first time
        //

        await goto(editPersonUrl)
        await expectFormValue('input#tuja-person__name', name)
        await expectFormValue('input#tuja-person__pno', pno)
        await expectFormValue('input#tuja-person__food', food)

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

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Ändringarna har sparats. Tack.')

        await goto(editPersonUrl)

        await expectFormValue('input#tuja-person__name', name)
        await expectFormValue('input#tuja-person__pno', pno)
        await expectFormValue('input#tuja-person__food', food)
      })

      it('should be possible to edit team members', async () => {
        const tempGroupProps = await signUpTeam()

        await goto(tempGroupProps.portalUrl)
        await clickLink('#tuja_edit_people_link')

        //
        // Verify data from when the team was registered
        //

        await expectElementCount('div.tuja-person-role-group_leader > div.tuja-people-existing > div.tuja-signup-person', 1)
        await expectElementCount('div.tuja-person-role-regular_group_member > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-adult_supervisor > div.tuja-people-existing > div.tuja-signup-person', 0)
        await expectElementCount('div.tuja-person-role-extra_contact > div.tuja-people-existing > div.tuja-signup-person', 0)

        await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__name__"]', tempGroupProps.teamLeader.name)
        await expectFormValue('div.tuja-person-role-group_leader input[name^="tuja-person__email__"]', tempGroupProps.teamLeader.email)
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

  describe('Crew', () => {

    let groupProps = null

    beforeAll(async () => {
      await asAdmin(async () => {
        // Go to admin console
        await goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Groups&tuja_competition=${competitionId}`)

        // Select crew category in tuja_new_group_type
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

    it('should be possible to sign up as crew member', async () => {
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
    ])('should not be possible to sign up as a crew member with bad data', async (name, email, phone, food, expectedErrorMessage) => {
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
