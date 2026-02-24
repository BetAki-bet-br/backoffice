import FootersPage from '../../support/page-objects/FootersPage';

describe('Footers', () => {
  const footersPage = new FootersPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/footers*', { fixture: 'footers/list.json' }).as('getFooters');
  });

  it('UC76: should list footers', () => {
    footersPage.visit();
    cy.wait('@getFooters');

    cy.get('#tbody tr').should('have.length', 2);
    cy.get('#tbody').should('contain.text', 'main-footer');
    cy.get('#tbody').should('contain.text', 'footer-pt');
  });

  it('UC77: should filter by status and country', () => {
    footersPage.visit();
    cy.wait('@getFooters');

    cy.intercept('GET', '/api/v1/footers*status=published*', {
      body: {
        data: [{ id: 1, key: 'main-footer', status: 'published', country: 'BR', brand: 'default', publish_at: '2025-01-01T00:00:00Z' }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('getFootersPublished');

    cy.get('#status').select('published');
    cy.get('#btnSearch').click();
    cy.wait('@getFootersPublished');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'main-footer');
  });

  it('UC78: should create a footer with translations', () => {
    footersPage.visit();
    cy.wait('@getFooters');

    cy.intercept('POST', '/api/v1/footers', {
      statusCode: 201,
      body: { data: { id: 3, key: 'new-footer', status: 'draft', country: 'BR' } },
    }).as('createFooter');

    cy.intercept('GET', '/api/v1/footers*', { fixture: 'footers/list.json' }).as('reloadFooters');

    footersPage.clickNew();
    cy.get('#editModal').should('be.visible');

    footersPage.fillKey('new-footer');
    footersPage.selectStatus('draft');
    footersPage.clickSave();

    cy.wait('@createFooter');
    cy.get('.toast').should('contain.text', 'criado');
  });

  it('UC79: should edit a footer', () => {
    footersPage.visit();
    cy.wait('@getFooters');

    cy.intercept('GET', '/api/v1/footers/1', {
      statusCode: 200,
      fixture: 'footers/single.json',
    }).as('getFooter');

    cy.intercept('PUT', '/api/v1/footers/1', {
      statusCode: 200,
      body: { data: { id: 1, key: 'main-footer-updated', status: 'published' } },
    }).as('updateFooter');

    cy.intercept('GET', '/api/v1/footers*', { fixture: 'footers/list.json' }).as('reloadFooters');

    footersPage.clickEditOnRow(1);
    cy.get('#editModal').should('be.visible');

    cy.get('#f_key').clear().type('main-footer-updated');
    footersPage.clickSave();

    cy.wait('@updateFooter');
    cy.get('.toast').should('contain.text', 'atualizado');
  });

  it('UC80: should manage footer links', () => {
    footersPage.visit();
    cy.wait('@getFooters');

    cy.intercept('GET', '/api/v1/footers/1', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          key: 'main-footer',
          links: [
            { block: 'main', label: 'Home', url: '/', icon: null, target: '_self', position: 1, is_active: true },
          ],
        },
      },
    }).as('getFooterLinks');

    cy.intercept('PUT', '/api/v1/footers/1/links', {
      statusCode: 200,
      body: { message: 'Links sincronizados.' },
    }).as('syncLinks');

    footersPage.clickLinksOnRow(1);
    cy.get('.modal').should('be.visible');
  });

  it('UC81: should publish a footer', () => {
    footersPage.visit();
    cy.wait('@getFooters');

    cy.intercept('POST', '/api/v1/footers/2/publish', {
      statusCode: 200,
      body: { data: { id: 2, status: 'published' } },
    }).as('publishFooter');

    cy.intercept('GET', '/api/v1/footers*', { fixture: 'footers/list.json' }).as('reloadFooters');

    footersPage.clickPublishOnRow(2);

    cy.wait('@publishFooter');
    cy.get('.toast').should('contain.text', 'publicado');
  });

  it('UC82: should delete a footer', () => {
    footersPage.visit();
    cy.wait('@getFooters');

    cy.intercept('DELETE', '/api/v1/footers/2', {
      statusCode: 200,
      body: {},
    }).as('deleteFooter');

    cy.intercept('GET', '/api/v1/footers*', { fixture: 'footers/list.json' }).as('reloadFooters');

    cy.on('window:confirm', () => true);

    footersPage.clickDeleteOnRow(2);

    cy.wait('@deleteFooter');
    cy.get('.toast').should('contain.text', 'excluído');
  });
});
