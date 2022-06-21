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
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Forms&tuja_competition=${competitionId}`)

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

      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Form&tuja_competition=${competitionId}&tuja_form=${id}`)

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
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Maps&tuja_competition=${competitionId}`)

      await adminPage.expectElementCount('a.tuja-map-link', 2)

      // Create new map
      const name = 'Alvik'
      await adminPage.type('#tuja_map_name', name)
      await adminPage.clickLink('#tuja_map_create_button')
      await adminPage.expectToContain('.notice-success', `Karta ${name} har lagts till.`)
      await adminPage.expectElementCount('a.tuja-map-link', 3)
      await adminPage.expectToContain('#tuja_map_create_map_result', '')

      const { mapId } = await adminPage.$eval('#tuja_map_create_map_result', node => ({ mapId: node.dataset.mapId }))
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Map&tuja_map=${mapId}&tuja_competition=${competitionId}`)
      await adminPage.expectFormValue('#tuja_map_name', name)

      // Delete map
      adminPage.page.once('dialog', async (dialog) => {
        await dialog.accept();
      });

      await adminPage.clickLink('#tuja_delete_button')
      await adminPage.expectToContain('.notice-success', `Kartan har tagits bort.`)
      await adminPage.clickLink('#tuja_maps_link')
      await adminPage.expectElementCount('a.tuja-map-link', 2)
    })

    it('should be possible to rename map and edit markers', async () => {
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=Map&tuja_map=${preexistingMapId}&tuja_competition=${competitionId}`)

      const fieldValuesBefore = await adminPage.$$eval('input.tuja-marker-raw-field', nodes => nodes.map(node => `${node.name}=${node.value}`))

      // Rename old map
      const newName = 'Bromma'
      await adminPage.type('#tuja_map_name', newName)
      await adminPage.clickLink('#tuja_save_button')
      await adminPage.expectFormValue('#tuja_map_name', newName)

      // Verify that map marker definitions have remained unchanged.
      const fieldValuesAfterRename = await adminPage.$$eval('input.tuja-marker-raw-field', nodes => nodes.map(node => `${node.name}=${node.value}`))
      expect(fieldValuesBefore).toEqual(fieldValuesAfterRename)

      // Update markers
      const markerFields = await adminPage.$$eval(`div.tuja-map-marker-controls`, nodes => nodes.map(node => ({
        nameFieldId: node.dataset.nameFieldId,
        nameFieldValue: document.getElementById(node.dataset.nameFieldId).value,
        latFieldId: node.dataset.latFieldId,
        longFieldId: node.dataset.longFieldId
      })))

      const { 
        nameFieldId: emptyFieldId,
        latFieldId: emptyLatFieldId,
        longFieldId: emptyLongFieldId,
      } = markerFields.filter(({ nameFieldValue }) => !nameFieldValue)[0]
      await adminPage.type(`#${emptyFieldId}`, 'Brommaplan')
      // Emulate map click:
      await adminPage.$eval(`#${emptyLatFieldId}`, node => node.value = '59.338609')
      await adminPage.$eval(`#${emptyLongFieldId}`, node => node.value = '17.939238')

      const { nameFieldId: nonEmptyFieldId, nameFieldValue: nonEmptyFieldValue } = markerFields.filter(({ nameFieldValue }) => !!nameFieldValue)[0]
      const updatedMarkerData = nonEmptyFieldValue + '.'
      await adminPage.type(`#${nonEmptyFieldId}`, updatedMarkerData)

      await adminPage.clickLink('#tuja_save_button')

      await adminPage.expectFormValue(`#${emptyFieldId}`, 'Brommaplan')
      await adminPage.expectFormValue(`#${emptyLatFieldId}`, '59.338609')
      await adminPage.expectFormValue(`#${emptyLongFieldId}`, '17.939238')
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

  describe('Team Members', () => {
    it('should be possible to add a new group member and later edit it', async () => {
      const expectFormValues = async (name, phone, email, food, pno, note) => {
        await adminPage.expectFormValue('#tuja_person_property__name', name)
        await adminPage.expectFormValue('#tuja_person_property__phone', phone)
        await adminPage.expectFormValue('#tuja_person_property__email', email)
        await adminPage.expectFormValue('#tuja_person_property__food', food)
        await adminPage.expectFormValue('#tuja_person_property__pno', pno)
        await adminPage.expectFormValue('#tuja_person_property__note', note)
      }

      // Create a test team
      const groupProps = await adminPage.addTeam()

      // Find and click link to get to the add-new-team-member page
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupMembers&tuja_competition=${competitionId}&tuja_group=${groupProps.id}`)
      await adminPage.clickLink('#tuja_group_member_add_link')

      // Add new team member
      await adminPage.type('#tuja_person_property__name', 'Amy Zingh')
      await adminPage.type('#tuja_person_property__phone', '070-000001')
      await adminPage.type('#tuja_person_property__email', 'amy.zingh@example.com')
      await adminPage.type('#tuja_person_property__food', 'No allergies')
      await adminPage.type('#tuja_person_property__pno', '2000-01-01')
      await adminPage.type('#tuja_person_property__note', 'I like warm hugs')
      await adminPage.clickLink('#tuja_group_member_save_button')
      const newPersonId = await adminPage.$eval(`#tuja_group_member_save_status`, node => parseInt(node.dataset.newPersonId))

      // Verify that form (still) shows what the user inputted
      await expectFormValues(
        'Amy Zingh',
        '+4670000001',
        'amy.zingh@example.com',
        'No allergies',
        '20000101-0000',
        'I like warm hugs')

      // Go back to complete list of team members
      await adminPage.goto(`http://localhost:8080/wp-admin/admin.php?page=tuja&tuja_view=GroupMembers&tuja_competition=${competitionId}&tuja_group=${groupProps.id}`)

      // Find the link to edit the new team member
      await adminPage.clickLink(`#tuja_group_member_link__${newPersonId}`)

      // Verify that form (still) shows what the user inputted
      await expectFormValues(
        'Amy Zingh',
        '+4670000001',
        'amy.zingh@example.com',
        'No allergies',
        '20000101-0000',
        'I like warm hugs')

      // Change the name
      await adminPage.type('#tuja_person_property__name', 'Amy Sing')
      await adminPage.clickLink('#tuja_group_member_save_button')

      // Verify that form (still) shows what the user inputted
      await expectFormValues(
        'Amy Sing',
        '+4670000001',
        'amy.zingh@example.com',
        'No allergies',
        '20000101-0000',
        'I like warm hugs')
    })
  })
})
