const querystring = require('querystring')
const puppeteer = require('puppeteer')
const faker = require('faker')

class PageWrapper {
  get page () {
    return this._page
  }

  async init () {
    console.log('🎁 Wrapping new incognito browser')
    // Create a new incognito browser context.
    const context = await browser.createIncognitoBrowserContext()
    // Create a new page in a pristine context.
    this._page = await context.newPage()
    return this
  }

  async type (selector, value) {
    const el = await this._page.$(selector)
    await el.evaluate(el => el.value = '')
    await el.type(value)
  }

  async goto (url, waitForNetwork = false) {
    console.log('➡️ Navigating to ', url)
    await Promise.all([
        this._page.waitForNavigation({ waitUntil: waitForNetwork ? 'networkidle2' : 'domcontentloaded', timeout: 10000 }),
        this._page.goto(url)
      ]
    )
  }

  async clickLink (selector) {
    await Promise.all([
        this._page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 10000 }),
        this._page.click(selector)
      ]
    )
  }

  async click (selector) {
    await this._page.click(selector)
  }

  async close () {
    await this._page.close()
  }

  async expectToContain (selector, expected) {
    const actual = await this._page.$eval(selector, node => node.innerText)
    await expect(actual).toContain(expected)
  }

  async expectSuccessMessage (expected) {
    await this.expectToContain('.tuja-message-success', expected)
  }

  async expectWarningMessage (expected) {
    await this.expectToContain('.tuja-message-warning', expected)
  }

  async expectInfoMessage (expected) {
    await this.expectToContain('.tuja-message-info', expected)
  }

  async expectErrorMessage (expected) {
    await this.expectToContain('.tuja-message-error', expected)
  }

  async expectFormValue (selector, expected) {
    const actual = await this._page.$eval(selector, node => node.value)
    await expect(actual).toBe(expected)
  }

  async expectPageTitle (expected) {
    expect(await this._page.title()).toContain(expected)
  }

  async expectElementCount (selector, expectedCount) {
    const actualArray = await this._page.$$(selector)
    if (actualArray.length !== expectedCount) {
      console.log(`💥 Selector ${selector} returned ${actualArray.length} items instead of ${expectedCount}`)
    }
    expect(actualArray).toHaveLength(expectedCount)
  }

  async $ (selector) {
    return this._page.$(selector)
  }

  async $eval (selector, func) {
    return this._page.$eval(selector, func)
  }

  async $$eval (selector, func) {
    return this._page.$$eval(selector, func)
  }

  async takeScreenshot () {
    const path = `screenshot-${new Date().getTime()}.png`
    // console.log('📷', path)
    await this._page.screenshot({ path: path, fullPage: true })
  }
}

const defaultPage = new PageWrapper()

const adminPage = new PageWrapper()

const click = async (selector) => defaultPage.page.click(selector)
const $ = async (selector, func) => defaultPage.$(selector, func)
const $eval = async (selector, func) => defaultPage.$eval(selector, func)
const $$eval = async (selector, func) => defaultPage.$$eval(selector, func)
const type = async (selector, value) => defaultPage.type(selector, value)
const goto = async (url, waitForNetwork = false) => defaultPage.goto(url, waitForNetwork)
const clickLink = async (selector) => defaultPage.clickLink(selector)
const expectToContain = async (selector, expected) => defaultPage.expectToContain(selector, expected)
const expectSuccessMessage = async (expected) => defaultPage.expectSuccessMessage(expected)
const expectInfoMessage = async (expected) => defaultPage.expectInfoMessage(expected)
const expectWarningMessage = async (expected) => defaultPage.expectWarningMessage(expected)
const expectErrorMessage = async (expected) => defaultPage.expectErrorMessage(expected)
const expectFormValue = async (selector, expected) => defaultPage.expectFormValue(selector, expected)
const expectPageTitle = async (expected) => defaultPage.expectPageTitle(expected)
const expectElementCount = async (selector, expectedCount) => defaultPage.expectElementCount(selector, expectedCount)
const takeScreenshot = async () => defaultPage.takeScreenshot()

describe('wp-tuja', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null

  const signUpTeam = async (isAutomaticallyAccepted = true) => {
    const name = faker.lorem.words()
    await goto(`http://localhost:8080/${competitionKey}/anmal`)
    await expectPageTitle(`Anmäl er till ${competitionName}`)
    await click('#tuja-group__age-0')
    await type('#tuja-group__name', name)
    await type('#tuja-person__name', 'Amber')
    await type('#tuja-person__email', 'amber@example.com')
    await type('#tuja-person__phone', '070-123456')
    await type('#tuja-person__pno', '19800101-1234')
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
    const groupPortalLinkNode = await $('#tuja_group_home_link')
    const portalUrl = await groupPortalLinkNode.evaluate(node => node.href)
    const key = await groupPortalLinkNode.evaluate(node => node.dataset.groupKey)
    const id = await groupPortalLinkNode.evaluate(node => node.dataset.groupId)
    return { key, id, portalUrl, name }
  }

  const initAdminPage = async () => {
    await adminPage.init()
    // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
    await adminPage.page.emulate(puppeteer.devices['iPad Pro landscape'])
    // Log in to Admin console
    await adminPage.goto('http://localhost:8080/wp-admin', true)
    await adminPage.type('#user_login', 'admin')
    await adminPage.type('#user_pass', 'admin')
    await adminPage.clickLink('#wp-submit')
  }

  const initUserPage = async (p) => {
    await p.init()
    // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
    await p.page.emulate(puppeteer.devices['iPhone 6 Plus'])
  }

  const createNewUserPage = async () => {
    const p = new PageWrapper()
    await initUserPage(p)
    return p
  }

  const createCompetition = async () => {
    // Go to Tuja page in Admin console
    await adminPage.goto('http://localhost:8080/wp-admin/admin.php?page=tuja')

    // Create new competition
    competitionName = 'Test Competition ' + new Date().getTime()

    await adminPage.type('#tuja_competition_name', competitionName)
    await adminPage.clickLink('#tuja_create_competition_button')

    const links = await adminPage.page.$$('form.tuja a')
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
        adminPage.page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        link.click()
      ]
    )

    competitionId = querystring.parse(resp.url()).tuja_competition
    await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Shortcodes&tuja_competition=${competitionId}`)

    const signUpLink = await adminPage.page.$('#tuja_shortcodes_competitionsignup_link')
    const signUpLinkUrl = await signUpLink.evaluate(node => node.href)

    competitionKey = signUpLinkUrl.split(/\//)[3]
    return ({ id: competitionId, key: competitionKey, name: competitionName })
  }

  const configureDefaultGroupStatus = async (status) => {
    await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

    await adminPage.click('#tuja_tab_groups')

    await adminPage.click(`#tuja_competition_settings_initial_group_status-${status}`)

    await adminPage.clickLink('#tuja_save_competition_settings_button')
  }

  const configureGroupCategories = async () => {
    await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=CompetitionSettings&tuja_competition=${competitionId}`)

    await adminPage.click('#tuja_tab_groups')

    const addGroupCategory = async (name, isCrew, ruleSetName) => {
      await adminPage.click('#tuja_add_group_category_button')
      const groupCategoryForm = await adminPage.page.$('div.tuja-groupcategory-form:last-of-type')
      const groupCategoryName = await groupCategoryForm.$('input[type=text]')
      const groupCategoryNameFieldName = await groupCategoryName.evaluate(node => node.name)
      const tempGroupCategoryId = groupCategoryNameFieldName.split(/__/)[2]
      await groupCategoryName.type(name)
      const groupCategoryRules = await groupCategoryForm.$('select[name="groupcategory__ruleset__' + tempGroupCategoryId + '"]')
      await groupCategoryRules.select(ruleSetName)
      await adminPage.page.click('input[name="groupcategory__iscrew__' + tempGroupCategoryId + '"][value="' + isCrew + '"]')
    }

    await addGroupCategory('Young Participants', false, 'tuja\\util\\rules\\YoungParticipantsRuleSet')
    await addGroupCategory('Old Participants', false, 'tuja\\util\\rules\\OlderParticipantsRuleSet')
    await addGroupCategory('The Crew', true, 'tuja\\util\\rules\\CrewMembersRuleSet')

    await adminPage.clickLink('#tuja_save_competition_settings_button')
  }

  beforeAll(async () => {

    jest.setTimeout(300000)

    await defaultPage.init()

    await initAdminPage()
    await initUserPage(defaultPage)

    const competitionData = await createCompetition()
    competitionId = competitionData.id
    competitionKey = competitionData.key
    competitionName = competitionData.name

    await configureDefaultGroupStatus('accepted')

    await configureGroupCategories()
  })

  describe('Answering forms', () => {

    let formId = 0
    let groupProps = null

    const createForm = async () => {
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Competition&tuja_competition=${competitionId}`)

      const formName = 'The Form'
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

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${formId}`)

      await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
      await adminPage.clickLink('div.tuja-admin-question a[href*="FormQuestions"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__images"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__choices"]')

      return formId
    }

    const configureFormDateLimits = async (startMinutes, endMinutes) => {
      const MINUTE = 60 * 1000
      const localTzOffsetMinutes = new Date().getTimezoneOffset()

      const formOpenDate = new Date(new Date().getTime() + startMinutes * MINUTE - localTzOffsetMinutes * MINUTE).toISOString().substr(0, 16)
      const formCloseDate = new Date(new Date().getTime() + endMinutes * MINUTE - localTzOffsetMinutes * MINUTE).toISOString().substr(0, 16)

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${formId}`)

      await adminPage.page.$eval('#tuja-submit-response-start', (node, date) => node.value = date, formOpenDate)
      await adminPage.page.$eval('#tuja-submit-response-end', (node, date) => node.value = date, formCloseDate)

      await adminPage.clickLink('button[name="tuja_action"][value="form_update"]')
    }

    beforeAll(async () => {
      groupProps = await signUpTeam()

      formId = await createForm()
    })

    it('should show the correct number of questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      await expectElementCount('section.tuja-question-group', 1)
      await expectElementCount('div.tuja-question', 4)
      await expectElementCount('input.tuja-fieldtext[type="text"]', 1)
      await expectElementCount('input.tuja-fieldtext[type="number"]', 1)
      await expectElementCount('div.tuja-image', 1)
      await expectElementCount('input.tuja-fieldchoices[type="radio"]', 3)
      await expectElementCount('button[name="tuja_formshortcode__action"][value="update"]', 1)
    })

    it('should be possible to answer radio button questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      const option = await $('input.tuja-fieldchoices[type="radio"]')
      await option.click()
      const id = await option.evaluate(node => node.id)

      await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      expect(await $eval('#' + id, node => node.checked)).toBeTruthy()
    })

    it('should be possible to answer free-text questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      await type('input.tuja-fieldtext[type="text"]', 'our answer')

      await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      await expectFormValue('input.tuja-fieldtext[type="text"]', 'our answer')
    })

    it('should be possible to answer number questions', async () => {
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      await type('input.tuja-fieldtext[type="number"]', '42')

      await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      await expectFormValue('input.tuja-fieldtext[type="number"]', '42')
    })

    it('should be possible to answer upload-image questions', async () => {
      const chooseFiles = async (files) => {
        const button = await defaultPage.page.waitForSelector('div.tuja-image-select.dropzone.dz-clickable')
        const buttonBoundingBox = await button.boundingBox()
        const [fileChooser] = await Promise.all([
          defaultPage.page.waitForFileChooser(),
          // We must manually click the top-left corner of the div.dropzone.dz-clickable element since we cannot use the
          // "element.click" function since it would click the center of the div.dropzone.dz-clickable element and
          // under some circumstances the center is actually covered by image thumbnails. Hence, we must click manually.
          defaultPage.page.mouse.click(buttonBoundingBox.x + 1, buttonBoundingBox.y + 1)
        ])
        await fileChooser.accept(files)

        // Wait for uploads to complete (successfully or not, but still completed)
        await Promise.all(files.map(file => defaultPage.page.waitForSelector(`div.dz-preview.dz-complete .dz-image img[alt="${file.substr(2)}"]`)))
      }

      const getFileUploadFieldsData = async () => defaultPage.$$eval(
        'div.tuja-image input[type="hidden"][name^="tuja_formshortcode__response__"][name$="[images][]"][value$=".jpeg"]',
        nodes => nodes.map(node => ({
          fileName: node.value,
          fileDigest: node.value.split(/\./)[0],
          thumbnailName: node.dataset.thumbnailUrl
        })))

      const saveAndVerifyUploads = async (forceReload = false) => {
        const uploadData = await getFileUploadFieldsData()

        await clickLink('button[name="tuja_formshortcode__action"][value="update"]')
        await expectSuccessMessage('Era svar har sparats')

        if (forceReload) {
          await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`, true)
        }

        for (const data of uploadData) {
          await expectElementCount('div.tuja-image input[type="hidden"][name*="tuja_formshortcode__response__"][value="' + data.fileName + '"]', 1)
          await expectElementCount('div.tuja-image-select img[src*="' + data.fileDigest + '"]', 1)
        }
        await expectElementCount('div.tuja-fieldimages div.dz-preview', uploadData.length)
      }

      const removeAllImages = async () => {
        const handles = await defaultPage.page.$$('div.dropzone button.remove-image')
        for (const handle of handles) {
          await handle.click()
        }
      }

      //
      // Upload one image
      //

      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)

      await chooseFiles(['./pexels-photo-1578484.jpeg'])

      await expectElementCount('div.tuja-fieldimages div.dz-preview', 1)

      await saveAndVerifyUploads(false)

      //
      // Remove uploaded images
      //
      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`, true)
      await removeAllImages()

      await expectElementCount('div.tuja-fieldimages div.dz-preview', 0)

      // Save and reload
      await saveAndVerifyUploads(true)

      await expectElementCount('div.tuja-fieldimages div.dz-preview', 0)

      //
      // Upload two images (but select 1+2 images)
      //

      await chooseFiles(['./pexels-photo-1578484.jpeg', './pexels-photo-2285996.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete .dz-image img[alt="pexels-photo-1578484.jpeg"]', 1)
      await expectElementCount('div.dz-preview.dz-complete .dz-image img[alt="pexels-photo-2285996.jpeg"]', 1)

      // Save (and verify that both images are saved)
      await saveAndVerifyUploads(false)

      // Remove all images
      await removeAllImages()

      // Upload one image
      await chooseFiles(['./pexels-photo-174667.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 1)
      await expectElementCount('.dz-image img[alt="pexels-photo-174667.jpeg"]', 1)

      // Try to upload two additional images (only one will succeed)
      await chooseFiles(['./pexels-photo-1578484.jpeg', './pexels-photo-2285996.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 2)
      await expectElementCount('.dz-image img[alt="pexels-photo-1578484.jpeg"]', 1)
      await expectElementCount('.dz-image img[alt="pexels-photo-174667.jpeg"]', 1)

      // Save (and verify that only two images are saved)
      await saveAndVerifyUploads(false)

      //
      // Replace uploaded images two new one (selected one by one) and make sure the Dropzone is disabled after
      // picking the second one.
      //

      // Remove all images
      await removeAllImages()
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 0)

      // Upload one image
      await chooseFiles(['./pexels-photo-1578484.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 1)

      // Upload one image more
      await chooseFiles(['./pexels-photo-174667.jpeg'])
      await expectElementCount('div.dz-preview.dz-complete.dz-success .dz-image img', 2)

      // Save (and verify that both images are saved)
      await saveAndVerifyUploads(false)
    })

    it('should NOT be possible to SEE questions BEFORE form has been OPENED', async () => {
      await configureFormDateLimits(10, 20)

      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      await expectWarningMessage('Formuläret kan inte visas just nu.')
      await expectElementCount('section.tuja-question-group', 0)
      await expectElementCount('div.tuja-question', 0)
      await expectElementCount('button[name="tuja_formshortcode__action"][value="update"]', 0)

      await configureFormDateLimits(-10, 10)
    })

    it('should NOT be possible to update answers AFTER form has been CLOSED', async () => {
      await configureFormDateLimits(-20, -10)

      await goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      await expectErrorMessage('Svar får inte skickas in nu.')
      await expectElementCount('button[name="tuja_formshortcode__action"][value="update"]', 0)

      await configureFormDateLimits(-10, 10)
    })

    it('should NOT be possible for two people in the same team to overwrite each others answers', async () => {
      const aliceSession = await createNewUserPage()
      const bobSession = await createNewUserPage()

      await aliceSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      await bobSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)

      // Alice answers first question only
      await aliceSession.type('input.tuja-fieldtext[type="text"]', 'The updated answer from Alice')
      await aliceSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')
      const expectedNumberAnswer = await aliceSession.$eval('input.tuja-fieldtext[type="number"]', node => node.value)

      await aliceSession.expectFormValue('input.tuja-fieldtext[type="text"]', 'The updated answer from Alice')

      // Bob answers first and second question. None of his answers should be kept.
      await bobSession.type('input.tuja-fieldtext[type="text"]', 'The answer from Bob')
      await bobSession.type('input.tuja-fieldtext[type="number"]', '1337')
      await bobSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      await bobSession.expectErrorMessage('Medan du fyllde i formuläret hann någon annan i ditt lag skicka in andra svar på några av frågorna.')

      // Alice's answer should still be there.
      await bobSession.expectFormValue('input.tuja-fieldtext[type="text"]', 'The updated answer from Alice')

      // Answer unchanged by Alice but changed by Bob should still be the original value since the logic does not know
      // if Alice cared about this value or not (we don't know if Alice wanted it to stay the same of if she's okay
      // with Bob changing it.)
      await bobSession.expectFormValue('input.tuja-fieldtext[type="number"]', expectedNumberAnswer)
    })

    it('should be possible for two people in the same team to give the same answer concurrently', async () => {
      const aliceSession = await createNewUserPage()
      const bobSession = await createNewUserPage()

      // Alice sets the initial answers
      await aliceSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`)
      await aliceSession.type('input.tuja-fieldtext[type="text"]', 'The answer')
      await aliceSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      await bobSession.goto(`http://localhost:8080/${groupProps.key}/svara/${formId}`) // Load Bob's form to get up-to-date lock value

      // Alice changes the answer to the first question
      await aliceSession.type('input.tuja-fieldtext[type="text"]', 'The new answer')
      await aliceSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      // Bob writes the same answer to the first question. Bob's lock value is out-of-date at this point.
      await bobSession.type('input.tuja-fieldtext[type="text"]', 'The new answer')
      await bobSession.clickLink('button[name="tuja_formshortcode__action"][value="update"]')

      // Bob gets no error message, even though his lock value is out-of-date, since he doesn't actually change anything compared to the latest answers (i.e. the answer updated by Alice).
      await bobSession.expectElementCount('.tuja-message-error', 0)
      await bobSession.expectFormValue('input.tuja-fieldtext[type="text"]', 'The new answer')
    })
  })

  describe('Tickets', () => {

    let stationsProps = null

    beforeAll(async () => {
      //
      // Configure stations
      //

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Stations&tuja_competition=${competitionId}`)

      await adminPage.type('#tuja_station_name', 'Hornstull')
      await adminPage.clickLink('#tuja_station_create_button')

      await adminPage.type('#tuja_station_name', 'Slussen')
      await adminPage.clickLink('#tuja_station_create_button')

      await adminPage.type('#tuja_station_name', 'Mariatorget')
      await adminPage.clickLink('#tuja_station_create_button')

      await adminPage.type('#tuja_station_name', 'Skanstull')
      await adminPage.clickLink('#tuja_station_create_button')

      //
      // Configure ticketing
      //

      stationsProps = await adminPage.page.$$eval('form.tuja a[data-key]', nodes => nodes.map(node => ({
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

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=StationsTicketing&tuja_competition=${competitionId}`)

      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[0].id}__colour"]`, (node, color) => node.value = color, stationsProps[0].colour)
      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[1].id}__colour"]`, (node, color) => node.value = color, stationsProps[1].colour)
      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[2].id}__colour"]`, (node, color) => node.value = color, stationsProps[2].colour)
      await adminPage.page.$eval(`input[name="tuja_ticketdesign__${stationsProps[3].id}__colour"]`, (node, color) => node.value = color, stationsProps[3].colour)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[0].id}__word"]`, stationsProps[0].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[1].id}__word"]`, stationsProps[1].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[2].id}__word"]`, stationsProps[2].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[3].id}__word"]`, stationsProps[3].word)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[0].id}__password"]`, stationsProps[0].password)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[1].id}__password"]`, stationsProps[1].password)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[2].id}__password"]`, stationsProps[2].password)
      await adminPage.type(`input[name="tuja_ticketdesign__${stationsProps[3].id}__password"]`, stationsProps[3].password)

      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[1].id}"]`, '1')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[2].id}"]`, '2')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[0].id}__${stationsProps[3].id}"]`, '3')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[1].id}__${stationsProps[2].id}"]`, '4')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[1].id}__${stationsProps[3].id}"]`, '5')
      await adminPage.type(`input[name="tuja_ticketcouponweight__${stationsProps[2].id}__${stationsProps[3].id}"]`, '6')

      await adminPage.clickLink('button[name="tuja_action"][value="save"]')
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
      const initialTicketWordsForTeamBob = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
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
      const ticketWordsAfterRetry = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
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
      const initialTicketWordsForTeamAlice = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
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
      const finalTicketWords = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
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
      const finalRetryTicketWords = await $$eval('div.tuja-ticket-word', nodes => nodes.map(node => node.textContent))
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

    describe('when teams need to be approved', () => {

      let groupProps = null

      beforeAll(async () => {
        await configureDefaultGroupStatus('awaiting_approval')
        groupProps = await signUpTeam(false)
      })

      afterAll(async () => {
        await configureDefaultGroupStatus('accepted')
      })

      it('the team portal becomes available after team has been accepted', async () => {
        const toBeAcceptedGroup = await signUpTeam(false)

        await goto(toBeAcceptedGroup.portalUrl)
        await expectWarningMessage('Ert lag står på väntelistan')
        await expectElementCount('div.entry-content p > a', 0) // No links shown
        await expectElementCount('div.entry-content p.tuja-message-success', 0)
        await expectElementCount('div.entry-content p.tuja-message-warning', 1)
        await expectElementCount('div.entry-content p.tuja-message-error', 0)

        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Group&tuja_competition=${competitionId}&tuja_group=${toBeAcceptedGroup.id}`)
        await adminPage.clickLink('button[name="tuja_points_action"][value="transition__accepted"]')

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

        await clickLink('button[name="tuja-action"]')

        await expectSuccessMessage('Tack')

        const editPersonUrl = await $eval('#tuja_signup_success_edit_link', node => node.href)
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

        const editPersonUrl = await $eval('#tuja_signup_success_edit_link', node => node.href)

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

      it('should evaluate registration rules for team size', async () => {
        const tempGroupProps = await signUpTeam()

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

  describe('Crew', () => {

    let crewGroupProps = null

    beforeAll(async () => {
      // Go to admin console
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Groups&tuja_competition=${competitionId}`)

      // Select crew category in tuja_new_group_type
      const id = await adminPage.$eval('select[name="tuja_new_group_type"] > option:last-child', node => node.value)
      await adminPage.page.select('select[name="tuja_new_group_type"]', id)

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
    })

    describe('report score', () => {
      let competingGroupProps = null
      let stationScoreReportForm = 0

      const createForm = async () => {
        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Competition&tuja_competition=${competitionId}`)

        const formName = 'The Stations'
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

        await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${formId}`)

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
        competingGroupProps = await signUpTeam()
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

      await type('input#tuja-person__name', 'Carol')
      await type('input#tuja-person__email', 'carol@example.com')
      await type('input#tuja-person__phone', '070-12345678')
      await type('input#tuja-person__food', 'Picky about eggs')

      await clickLink('button[name="tuja-action"]')

      await expectSuccessMessage('Tack')

      const editPersonUrl = await $eval('#tuja_signup_success_edit_link', node => node.href)
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
      await goto(`http://localhost:8080/${crewGroupProps.key}/anmal-mig`)

      await type('input[name^="tuja-person__name"]', name)
      await type('input[name^="tuja-person__email"]', email)
      await type('input[name^="tuja-person__phone"]', phone)
      await type('input[name^="tuja-person__food"]', food)

      await clickLink('button[name="tuja-action"]')

      await expectErrorMessage(expectedErrorMessage)
    })
  })

  describe('Checking in', () => {
    it('basic tests', async () => {
      const tempGroupProps = await signUpTeam()

      // cannot check in unless status is AWAITING_CHECKIN
      await goto(`http://localhost:8080/${tempGroupProps.key}/incheckning`)
      await expectErrorMessage('Incheckningen är inte öppen för ert lag.')
      await expectElementCount('button', 0)
      await expectElementCount('input', 0)

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Group&tuja_competition=${competitionId}&tuja_group=${tempGroupProps.id}`)
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

    let groupProps = null

    beforeAll(async () => {
      groupProps = await signUpTeam(true)
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Group&tuja_competition=${competitionId}&tuja_group=${groupProps.id}`)
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
})
