const ASSETS_FOLDER = './assets/'

class PageWrapper {
  constructor (browser) {
    this.browser = browser
  }

  get page () {
    return this._page
  }

  async init () {
    // Create a new incognito browser context.
    const context = await this.browser.createIncognitoBrowserContext()
    // Create a new page in a pristine context.
    this._page = await context.newPage()
    return this
  }

  async type (selector, value) {
    const el = await this._page.$(selector)
    if (!el) {
      throw new Error(`Could not find ${selector}`)
    }
    await el.evaluate(el => el.value = '')
    await el.type(value)
  }

  async goto (url, waitForNetwork = false) {
    await Promise.all([
        this._page.waitForNavigation({ waitUntil: waitForNetwork ? 'networkidle2' : 'domcontentloaded', timeout: 30000 }),
        this._page.goto(url)
      ]
    )
  }

  async clickLink (selector) {
    await Promise.all([
        this._page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
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
    expect(actual).toContain(expected)
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

  async $$ (selector) {
    return this._page.$$(selector)
  }

  async $eval (selector, func, ...funcArgs) {
    return this._page.$eval(selector, func, ...funcArgs)
  }

  async $$eval (selector, func, ...funcArgs) {
    return this._page.$$eval(selector, func, ...funcArgs)
  }

  async takeScreenshot () {
    const label = expect.getState().currentTestName.toLowerCase().replace(/[^a-z0-9]/g, '-').substr(0, 70)
    const timestamp = new Date().getTime()
    const path = `screenshot-${timestamp}-${label}.png`
    await this._page.screenshot({ path: path, fullPage: true })
  }

  async setDateInput (selector, secondsFromNow) {
    const localTzOffsetSeconds = new Date().getTimezoneOffset() * 60

    const dateStr = new Date(new Date().getTime() + (secondsFromNow - localTzOffsetSeconds) * 1000).toISOString().substr(0, 16)

    await this.$eval(selector, (node, date) => node.value = date, dateStr)
  }

  async chooseFiles (files) {
    const [fileChooser] = await Promise.all([
      this._page.waitForFileChooser(),
      this._page.click('button.tuja-image-add.dz-clickable')
    ])
    await fileChooser.accept(files.map(file => ASSETS_FOLDER + file))

    // Wait for uploads to complete (successfully or not, but still completed)
    await Promise.all(files.map(file => this._page.waitForSelector(`div.dz-preview.dz-complete .dz-image img[alt="${file}"]`)))
  }
}

module.exports = PageWrapper
