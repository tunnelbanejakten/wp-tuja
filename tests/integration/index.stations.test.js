const AdminPageWrapper = require('./utils/adminpagewrapper')
const UserPageWrapper = require('./utils/userpagewrapper')

let adminPage = null

jest.setTimeout(300000)

describe('Stations', () => {

  let competitionId = null
  let competitionKey = null
  let competitionName = null
  let stationIds = null

  beforeAll(async () => {
    competitionId = global.competitionId
    competitionKey = global.competitionKey
    competitionName = global.competitionName
    stationIds = global.stationIds
    adminPage = await (new AdminPageWrapper(browser).init())
  })

  afterAll(async () => {
    await adminPage.close()
  })

  describe('Points and scoring', () => {
    it('set points for any team and for any station (also some bonus points)', async () => {
      const groupAliceProps = await adminPage.addTeam()
      const groupBobProps = await adminPage.addTeam()

      // Goto StationsPoints
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=StationsPoints&tuja_competition=${competitionId}`)
      
      // Enter points
      await adminPage.type(`#tuja__station-points__${stationIds[0]}__${groupAliceProps.id}`, '1')
      await adminPage.type(`#tuja__station-points__${stationIds[1]}__${groupAliceProps.id}`, '2')
      await adminPage.type(`#tuja__station-points__${stationIds[2]}__${groupAliceProps.id}`, '3')
      await adminPage.type(`#tuja__station-points__${stationIds[0]}__${groupBobProps.id}`, '4')
      await adminPage.type(`#tuja__station-points__${stationIds[1]}__${groupBobProps.id}`, '5')
      await adminPage.type(`#tuja__station-points__${stationIds[2]}__${groupBobProps.id}`, '6')
      await adminPage.clickLink('button[name="tuja_action"][value="save"]')
      
      // Visit team pages and check total scores  
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupScore&tuja_competition=${competitionId}&tuja_group=${groupAliceProps.id}`)
      const totalFinalAlice = await adminPage.$eval(`#tuja-group-score`, node => parseInt(node.dataset.totalFinal))
      expect(totalFinalAlice).toBe(6)

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupScore&tuja_competition=${competitionId}&tuja_group=${groupBobProps.id}`)
      const totalFinalBob = await adminPage.$eval(`#tuja-group-score`, node => parseInt(node.dataset.totalFinal))
      expect(totalFinalBob).toBe(15)

      // Goto ExtraPoints
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=ExtraPoints&tuja_competition=${competitionId}`)
      
      // Enter points
      await adminPage.type(`#tuja__extra-points__0__-1`, 'Bonuspoäng')
      await adminPage.type(`#tuja__extra-points__0__${groupAliceProps.id}`, '7')
      await adminPage.type(`#tuja__extra-points__0__${groupBobProps.id}`, '8')
      await adminPage.clickLink('button[name="tuja_action"][value="save"]')

      const existingLabelCrc32 = '3938429584' // CRC32 value for 'Bonuspoäng'
      const anotherExistingLabelCrc32 = '737937151' // CRC32 value for 'Fler poäng'
      
      // Visit team pages and check total scores  
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupScore&tuja_competition=${competitionId}&tuja_group=${groupAliceProps.id}`)
      const totalFinalAliceWithExtra = await adminPage.$eval(`#tuja-group-score`, node => parseInt(node.dataset.totalFinal))
      expect(totalFinalAliceWithExtra).toBe(6 + 7)
      
      // Verify that Bonuspoäng still has 7 points
      await adminPage.expectFormValue(`#tuja__extra-points__${existingLabelCrc32}`, '7')

      // Add new "extra points" label with 9 points
      await adminPage.type(`#tuja__extra-points-label__0`, 'Fler poäng')
      await adminPage.type(`#tuja__extra-points__0`, '9')

      // Save changes and verify that total score has increased by 9 after page has reloaded
      await adminPage.clickLink('button[name="tuja_points_action"][value="save_stations_and_extras"]')
      const totalFinalAliceWithExtra2 = await adminPage.$eval(`#tuja-group-score`, node => parseInt(node.dataset.totalFinal))
      expect(totalFinalAliceWithExtra2).toBe(6 + 7 + 9)

      await adminPage.expectFormValue(`#tuja__extra-points__${existingLabelCrc32}`, '7')
      await adminPage.expectFormValue(`#tuja__extra-points__${anotherExistingLabelCrc32}`, '9')

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupScore&tuja_competition=${competitionId}&tuja_group=${groupBobProps.id}`)
      const totalFinalBobWithExtra = await adminPage.$eval(`#tuja-group-score`, node => parseInt(node.dataset.totalFinal))
      expect(totalFinalBobWithExtra).toBe(15 + 8)
      
      // Verify that new "extra points" label is available (and empty)
      await adminPage.expectFormValue(`#tuja__extra-points__${existingLabelCrc32}`, '8')
      await adminPage.expectFormValue(`#tuja__extra-points__${anotherExistingLabelCrc32}`, '')
    })
  })
})
