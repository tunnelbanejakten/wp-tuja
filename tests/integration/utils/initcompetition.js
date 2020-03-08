const PageWrapper = require('./pagewrapper')
const AdminPageWrapper = require('./adminpagewrapper')
const puppeteer = require('puppeteer')
const faker = require('faker')
const querystring = require('querystring')

const createCompetition = async (adminPage) => {
  // Go to Tuja page in Admin console
  await adminPage.goto('http://localhost:8080/wp-admin/admin.php?page=tuja_admin')

  // Create new competition
  const competitionName = 'Test Competition ' + new Date().getTime()

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

  const competitionId = querystring.parse(resp.url()).tuja_competition
  await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Shortcodes&tuja_competition=${competitionId}`)

  const signUpLink = await adminPage.page.$('#tuja_shortcodes_competitionsignup_link')
  const signUpLinkUrl = await signUpLink.evaluate(node => node.href)

  const competitionKey = signUpLinkUrl.split(/\//)[3]
  return ({
    id: competitionId,
    key: competitionKey,
    name: competitionName
  })
}

module.exports = async (browser) => {
  const adminPage = await (new AdminPageWrapper(browser).init())

  const competitionData = await createCompetition(adminPage)

  await adminPage.configureDefaultGroupStatus(competitionData.id, 'accepted')

  const crewGroupCategoryId = await adminPage.configureGroupCategories(competitionData.id)

  return ({
    crewGroupCategoryId,
    ...competitionData
  })
}