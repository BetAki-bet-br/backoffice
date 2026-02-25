import SlotsPage from '../../support/page-objects/SlotsPage';

describe('Slots', () => {
  const slotsPage = new SlotsPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/slots*', { fixture: 'slots/list.json' }).as('getSlots');
  });

  it('UC29: should list slots with pagination', () => {
    slotsPage.visit();
    cy.wait('@getSlots');

    cy.get('#tbody tr').should('have.length', 3);
    cy.get('#tbody').should('contain.text', 'Sweet Bonanza');
    cy.get('#tbody').should('contain.text', 'Gates of Olympus');
    cy.get('#tbody').should('contain.text', 'Big Bass Bonanza');

    slotsPage.getPaginationInfo().should('contain.text', 'Carregados: 3');
  });

  it('UC30: should search slots by title/provider/game_id', () => {
    slotsPage.visit();
    cy.wait('@getSlots');

    cy.intercept('GET', '/api/v1/slots*q=Sweet*', {
      body: {
        data: [{ id: 1, title: 'Sweet Bonanza', status: 'active', provider: 'Pragmatic Play', provider_game_id: 'vs20fruitsw', cover_url: null }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('searchSlots');

    slotsPage.searchByQuery('Sweet');
    cy.wait('@searchSlots');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'Sweet Bonanza');
  });

  it('UC31: should filter slots by status', () => {
    slotsPage.visit();
    cy.wait('@getSlots');

    cy.intercept('GET', '/api/v1/slots*status=inactive*', {
      body: {
        data: [{ id: 3, title: 'Big Bass Bonanza', status: 'inactive', provider: 'Pragmatic Play', provider_game_id: 'vs10bbbonanza', cover_url: null }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getSlotsInactive');

    slotsPage.filterByStatus('inactive');
    cy.wait('@getSlotsInactive');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'Big Bass Bonanza');
  });

  it('UC32: should create a new slot', () => {
    slotsPage.visit();
    cy.wait('@getSlots');

    cy.intercept('POST', '/api/v1/slots', {
      statusCode: 201,
      body: { data: { id: 4, title: 'New Slot', status: 'active', provider: 'Test Provider', provider_game_id: 'test123' } },
    }).as('createSlot');

    cy.intercept('GET', '/api/v1/slots*', { fixture: 'slots/list.json' }).as('reloadSlots');

    slotsPage.clickNew();

    cy.get('#editModal').should('be.visible');
    cy.get('#editTitle').should('contain.text', 'Novo Slot');

    slotsPage.fillTitle('New Slot');
    slotsPage.selectStatus('active');
    slotsPage.fillProvider('Test Provider');
    slotsPage.fillProviderGameId('test123');
    slotsPage.clickSave();

    cy.wait('@createSlot').its('request.body').should('deep.include', {
      title: 'New Slot',
      status: 'active',
      provider: 'Test Provider',
      provider_game_id: 'test123',
    });

    cy.get('.toast').should('contain.text', 'Slot criado');
  });

  it('UC33: should edit an existing slot', () => {
    slotsPage.visit();
    cy.wait('@getSlots');

    cy.intercept('GET', '/api/v1/slots/1', {
      statusCode: 200,
      fixture: 'slots/single.json',
    }).as('getSlot');

    cy.intercept('PUT', '/api/v1/slots/1', {
      statusCode: 200,
      body: { data: { id: 1, title: 'Sweet Bonanza Updated', status: 'active' } },
    }).as('updateSlot');

    cy.intercept('GET', '/api/v1/slots*', { fixture: 'slots/list.json' }).as('reloadSlots');

    slotsPage.clickEditOnRow(1);

    cy.wait('@getSlot');
    cy.get('#editModal').should('be.visible');
    cy.get('#editTitle').should('contain.text', 'Editar Slot #1');

    cy.get('#f_title').clear().type('Sweet Bonanza Updated');
    slotsPage.clickSave();

    cy.wait('@updateSlot');
    cy.get('.toast').should('contain.text', 'Slot atualizado');
  });

  it('UC34: should delete a slot', () => {
    slotsPage.visit();
    cy.wait('@getSlots');

    cy.intercept('DELETE', '/api/v1/slots/3', {
      statusCode: 200,
      body: {},
    }).as('deleteSlot');

    cy.intercept('GET', '/api/v1/slots*', { fixture: 'slots/list.json' }).as('reloadSlots');

    cy.on('window:confirm', () => true);

    slotsPage.clickDeleteOnRow(3);

    cy.wait('@deleteSlot');
    cy.get('.toast').should('contain.text', 'Slot excluído');
  });
});
