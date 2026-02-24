import TopListsPage from '../../support/page-objects/TopListsPage';

describe('Top Lists', () => {
  const topListsPage = new TopListsPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/top-lists*', { fixture: 'toplists/list.json' }).as('getTopLists');
  });

  it('UC58: should list top lists', () => {
    topListsPage.visit();
    cy.wait('@getTopLists');

    cy.get('#tbody tr').should('have.length', 2);
    cy.get('#tbody').should('contain.text', 'Top 10 Janeiro');
    cy.get('#tbody').should('contain.text', 'Top 10 Fevereiro');
  });

  it('UC59: should filter by status', () => {
    topListsPage.visit();
    cy.wait('@getTopLists');

    cy.intercept('GET', '/api/v1/top-lists*status=published*', {
      body: {
        data: [{ id: 1, title: 'Top 10 Janeiro', slug: 'top-10-janeiro', status: 'published', vertical: 'slots', type: 'manual', valid_from: '2025-01-01', valid_until: '2025-01-31', slots_count: 10 }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getTopListsPublished');

    cy.get('#status').select('published');
    cy.get('#btnSearch').click();
    cy.wait('@getTopListsPublished');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'Top 10 Janeiro');
  });

  it('UC60: should create a top list', () => {
    topListsPage.visit();
    cy.wait('@getTopLists');

    cy.intercept('POST', '/api/v1/top-lists', {
      statusCode: 201,
      body: { data: { id: 3, title: 'Top 10 Março', slug: 'top-10-marco', status: 'draft' } },
    }).as('createTopList');

    cy.intercept('GET', '/api/v1/top-lists*', { fixture: 'toplists/list.json' }).as('reloadTopLists');

    topListsPage.clickNew();
    cy.get('#editModal').should('be.visible');

    topListsPage.fillTitle('Top 10 Março');
    topListsPage.clickSave();

    cy.wait('@createTopList');
    cy.get('.toast').should('contain.text', 'criado');
  });

  it('UC61: should edit a top list', () => {
    topListsPage.visit();
    cy.wait('@getTopLists');

    cy.intercept('GET', '/api/v1/top-lists/1', {
      statusCode: 200,
      fixture: 'toplists/single.json',
    }).as('getTopList');

    cy.intercept('PUT', '/api/v1/top-lists/1', {
      statusCode: 200,
      body: { data: { id: 1, title: 'Top 10 Janeiro Updated', status: 'published' } },
    }).as('updateTopList');

    cy.intercept('GET', '/api/v1/top-lists*', { fixture: 'toplists/list.json' }).as('reloadTopLists');

    topListsPage.clickEditOnRow(1);
    cy.get('#editModal').should('be.visible');

    cy.get('#f_title').clear().type('Top 10 Janeiro Updated');
    topListsPage.clickSave();

    cy.wait('@updateTopList');
    cy.get('.toast').should('contain.text', 'atualizado');
  });

  it('UC62: should manage slots of a top list', () => {
    topListsPage.visit();
    cy.wait('@getTopLists');

    cy.intercept('GET', '/api/v1/top-lists/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          title: 'Top 10 Janeiro',
          slots: [{ id: 1, title: 'Sweet Bonanza', pivot: { position: 1 } }],
        },
      },
    }).as('getTopListSlots');

    cy.intercept('PUT', '/api/v1/top-lists/1/slots', {
      statusCode: 200,
      body: { message: 'Slots sincronizados.' },
    }).as('syncTopListSlots');

    topListsPage.clickSlotsOnRow(1);
    cy.get('.modal').should('be.visible');
  });

  it('UC63: should publish a top list', () => {
    topListsPage.visit();
    cy.wait('@getTopLists');

    cy.intercept('POST', '/api/v1/top-lists/2/publish', {
      statusCode: 200,
      body: { data: { id: 2, status: 'published' } },
    }).as('publishTopList');

    cy.intercept('GET', '/api/v1/top-lists*', { fixture: 'toplists/list.json' }).as('reloadTopLists');

    topListsPage.clickPublishOnRow(2);

    cy.wait('@publishTopList');
    cy.get('.toast').should('contain.text', 'publicado');
  });
});
