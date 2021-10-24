const PageWrapper = require('./pagewrapper')
const AdminPageWrapper = require('./adminpagewrapper')
const puppeteer = require('puppeteer')
const faker = require('faker')
const querystring = require('querystring')

const createCompetition = async (adminPage) => {
  // Go to Tuja page in Admin console
  await adminPage.goto('http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=CompetitionBootstrap')

  // Create new competition
  const competitionName = 'Test ' + new Date().toLocaleString('se-SV', { timeZone: 'UTC' })

  await adminPage.type('#tuja_competition_name', competitionName)
  await adminPage.click('#tuja_create_default_group_categories')
  await adminPage.click('#tuja_create_default_crew_groups')
  await adminPage.click('#tuja_create_common_group_state_transition_sendout_templates')
  await adminPage.click('#tuja_create_sample_form')
  await adminPage.click('#tuja_create_sample_maps')
  await adminPage.click('#tuja_create_sample_stations')
  await adminPage.clickLink('#tuja_competition_bootstrap_button')

  const continueLink = await adminPage.page.$('#tuja_bootstrapped_competition_link')
  const ids = await continueLink.evaluate(node => ({ id: node.dataset.competitionId, key: node.dataset.competitionKey }))
  return ({
    id: ids.id,
    key: ids.key,
    name: competitionName
  })
}

module.exports = async (browser) => {
  const adminPage = await (new AdminPageWrapper(browser).init())

  const competitionData = await createCompetition(adminPage)

  await adminPage.configurePaymentDetails(competitionData.id)

  await adminPage.configureDefaultGroupStatus(competitionData.id, 'accepted')

  const crewGroupCategoryId = await adminPage.configureGroupCategories(competitionData.id)

  return ({
    crewGroupCategoryId,
    ...competitionData
  })
}