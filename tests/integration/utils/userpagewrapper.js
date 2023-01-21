const PageWrapper = require('./pagewrapper')
const puppeteer = require('puppeteer')

const CITIES = ['Norrmalm', 'Gothenburg', 'Nacka', 'Kungsholmen', 'Solna', 'Farsta']
const ADJECTIVES = ['Adventurous', 'Awesome', 'Five', 'Chill', 'Grumpy', 'Sly']
const ANIMALS = ['Moose', 'Wolves', 'Bears', 'Lynx', 'Foxes', 'Wolverines', 'Boars', 'Otters', 'Lemmings']

class UserPageWrapper extends PageWrapper {
  // competitionId
  // competitionKey

  constructor(browser, competitionId, competitionKey) {
    super(browser)
    this.competitionId = competitionId
    this.competitionKey = competitionKey
  }

  async init() {
    await super.init()

    // Device emulator list: https://github.com/puppeteer/puppeteer/blob/master/lib/DeviceDescriptors.js
    await this.page.emulate(puppeteer.devices['iPhone 6 Plus'])

    return this
  }

  async type(selector, value) {
    await super.type(`#content ${selector}`, value)
  }
  async clickLink(selector) {
    await super.clickLink(`#content ${selector}`)
  }
  async click(selector) {
    await super.click(`#content ${selector}`)
  }
  async expectToContain(selector, expected) {
    await super.expectToContain(`#content ${selector}`, expected)
  }
  async expectFormValue(selector, expected) {
    await super.expectFormValue(`#content ${selector}`, expected)
  }
  async expectElementCount(selector, expectedCount) {
    await super.expectElementCount(`#content ${selector}`, expectedCount)
  }
  async $(selector) {
    return await super.$(`#content ${selector}`)
  }
  async $$(selector) {
    return await super.$$(`#content ${selector}`)
  }
  async $eval(selector, func, ...funcArgs) {
    return await super.$eval(`#content ${selector}`, func, ...funcArgs)
  }
  async $$eval(selector, func, ...funcArgs) {
    return await super.$$eval(`#content ${selector}`, func, ...funcArgs)
  }

  async signUpTeam(adminPage, isAutomaticallyAccepted = true, isGroupLeader = true, isCitySpecified = false, categoryName = 'Old Participants') {
    await adminPage.configureEventDateLimits(this.competitionId, 7 * 24 * 60, 7 * 24 * 60 + 60)
    const name = [
      ADJECTIVES,
      ANIMALS,
    ].map(options => options[Math.floor(Math.random() * options.length)]).join(' ')
    const city = CITIES[Math.floor(Math.random() * CITIES.length)]

    await this.goto(`http://localhost:8080/${this.competitionKey}/anmal`)
    await this.click(`input[name="tuja-group__age"][value="${categoryName}"]`)
    if (isGroupLeader) {
      await this.click('#tuja-person__role-0')
    } else {
      await this.click('#tuja-person__role-1')
    }
    await this.type('#tuja-group__name', name)
    if (isCitySpecified) {
      await this.type('#tuja-group__city', city)
    }
    const randomDigits = Math.random().toFixed(5).substring(2) // 5 random digits
    const emailAddress = `amber-${randomDigits}@example.com`
    await this.type('#tuja-person__email__', emailAddress)
    if (isGroupLeader) {
      await this.type('#tuja-person__name__', 'Amber')
      await this.type('#tuja-person__phone__', '070-123456')
      await this.type('#tuja-person__pno__', '19800101-1234')
    }
    if (isAutomaticallyAccepted) {
      await this.expectToContain('#tuja_signup_button', 'Anmäl lag')
    } else {
      await this.expectToContain('#tuja_signup_button', 'Anmäl lag till väntelista')
      await this.expectWarningMessage('Varför väntelista?')
    }
    await this.click('#tuja-terms_and_conditions-0')
    await this.clickLink('#tuja_signup_button')
    if (isAutomaticallyAccepted) {
      await this.expectSuccessMessage('Tack')
    } else {
      await this.expectWarningMessage('Ert lag står på väntelistan')
    }
    const groupPortalLinkNode = await this.$('#tuja_group_home_link')
    const portalUrl = await groupPortalLinkNode.evaluate(node => node.href)
    const key = await groupPortalLinkNode.evaluate(node => node.dataset.groupKey)
    const id = await groupPortalLinkNode.evaluate(node => node.dataset.groupId)
    return { key, id, portalUrl, name, city, emailAddress }
  }

}

module.exports = UserPageWrapper