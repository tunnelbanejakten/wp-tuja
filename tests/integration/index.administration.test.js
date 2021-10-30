const AdminPageWrapper = require('./utils/adminpagewrapper')

let adminPage = null

jest.setTimeout(300000)

describe('Administration', () => {

  let competitionId = null

  beforeAll(async () => {
    competitionId = global.competitionId
    adminPage = await (new AdminPageWrapper(browser).init())
  })

  afterAll(async () => {
    await adminPage.close()
  })

  describe('Forms', () => {

    let formKey = 0
    let formId = 0

    beforeAll(async () => {
      formKey = global.formKey
      formId = global.formId
    })

    it('should be able to create a new form', async () => {
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Competition&tuja_competition=${competitionId}`)

      const formName = 'New Form'
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

      const formIds = await link.evaluate(node => ({ id: node.dataset.id, key: node.dataset.randomId }))

      const { id, key } = formIds

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${id}`)

      await adminPage.clickLink('button[name="tuja_action"][value="question_group_create"]')
      await adminPage.clickLink('div.tuja-admin-question a[href*="FormQuestions"]')
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__images"]')
      await adminPage.expectElementCount('div.tuja-admin-question-imagesquestion', 1)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__text"]')
      await adminPage.expectElementCount('div.tuja-admin-question-textquestion', 1)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__number"]')
      await adminPage.expectElementCount('div.tuja-admin-question-numberquestion', 1)
      await adminPage.clickLink('button[name="tuja_action"][value="question_create__choices"]')
      await adminPage.expectElementCount('div.tuja-admin-question-optionsquestion', 1)
    })
  })

  describe('Maps', () => {
    let preexistingMapId = 0

    beforeAll(async () => {
      preexistingMapId = global.mapId
    })

    it('should be possible to add and remove map', async () => {
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Maps&tuja_competition=${competitionId}`)

      await adminPage.expectElementCount('input.tuja-map-name-field', 2)
      const fieldValuesBefore = await adminPage.$$eval('input.tuja-marker-raw-field', nodes => nodes.map(node => `${node.name}=${node.value}`))

      // Create new map
      const name = 'Alvik'
      await adminPage.type('#tuja_map_name', name)
      await adminPage.clickLink('#tuja_map_create_button')
      await adminPage.expectToContain('.notice-success', `Karta ${name} har lagts till.`)
      await adminPage.expectElementCount('input.tuja-map-name-field', 3)
      await adminPage.expectToContain('#tuja_map_create_map_result', '')

      const { mapId } = await adminPage.$eval('#tuja_map_create_map_result', node => ({ mapId: node.dataset.mapId }))
      await adminPage.expectFormValue(`#tuja_map_name__${mapId}`, name)

      // Delete map
      adminPage.page.once('dialog', async (dialog) => {
        await dialog.accept();
      });

      await adminPage.clickLink(`#tuja_map_delete__${mapId}`)
      await adminPage.expectToContain('.notice-success', `Kartan togs bort.`)
      await adminPage.expectElementCount('input.tuja-map-name-field', 2)

      // Verify that other maps have remained unchanged.
      const fieldValuesAfter = await adminPage.$$eval('input.tuja-marker-raw-field', nodes => nodes.map(node => `${node.name}=${node.value}`))
      expect(fieldValuesBefore).toEqual(fieldValuesAfter)
    })

    it('should be possible to rename map and edit markers', async () => {
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja_admin&tuja_view=Maps&tuja_competition=${competitionId}`)

      const fieldValuesBefore = await adminPage.$$eval('input.tuja-marker-raw-field', nodes => nodes.map(node => `${node.name}=${node.value}`))

      // Rename old map
      const newName = 'Bromma'
      await adminPage.type(`#tuja_map_name__${preexistingMapId}`, newName)
      await adminPage.clickLink('#tuja_save_button')
      await adminPage.expectFormValue(`#tuja_map_name__${preexistingMapId}`, newName)

      // Verify that map marker definitions have remained unchanged.
      const fieldValuesAfterRename = await adminPage.$$eval('input.tuja-marker-raw-field', nodes => nodes.map(node => `${node.name}=${node.value}`))
      expect(fieldValuesBefore).toEqual(fieldValuesAfterRename)

      // Update markers
      const markerFields = await adminPage.$$eval(`input.tuja-marker-raw-field[id^="tuja_marker_raw__${preexistingMapId}__"]`, nodes => nodes.map(node => ({ id: node.id, value: node.value })))

      const { id: emptyFieldId } = markerFields.filter(({ value }) => !value)[0]
      const newMarkerData = '59.338609 17.939238 Brommaplan'
      await adminPage.type(`#${emptyFieldId}`, newMarkerData)
      
      const { id: nonEmptyFieldId, value: nonEmptyFieldValue } = markerFields.filter(({ value }) => !!value)[0]
      const updatedMarkerData = nonEmptyFieldValue + '.'
      await adminPage.type(`#${nonEmptyFieldId}`, updatedMarkerData)
      
      await adminPage.clickLink('#tuja_save_button')

      await adminPage.expectFormValue(`#${emptyFieldId}`, newMarkerData)
      await adminPage.expectFormValue(`#${nonEmptyFieldId}`, updatedMarkerData)

      // Verify that other map marker definitions have remained unchanged.
      const fieldValuesAfterUpdate = await adminPage.$$eval('input.tuja-marker-raw-field', nodes => nodes.map(node => `${node.name}=${node.value}`))
      expect(
        fieldValuesBefore.filter(s => !s.includes(emptyFieldId) && !s.includes(nonEmptyFieldId))
      ).toEqual(
        fieldValuesAfterUpdate.filter(s => !s.includes(emptyFieldId) && !s.includes(nonEmptyFieldId))
      )
    })
  })
})
