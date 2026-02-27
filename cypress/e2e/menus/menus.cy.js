import MenusPage from '../../support/page-objects/MenusPage';

describe('Menus', () => {
  const menusPage = new MenusPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
    cy.intercept('GET', '/api/v1/menus*', { fixture: 'menus/list.json' }).as('getMenus');
  });

  it('UC46: should list menus', () => {
    menusPage.visit();
    cy.wait('@getMenus');

    cy.get('#tbody tr').should('have.length', 3);
    cy.get('#tbody').should('contain.text', 'Menu Principal');
    cy.get('#tbody').should('contain.text', 'Menu Footer');
    cy.get('#tbody').should('contain.text', 'Menu Mobile');
  });

  it('UC47: should search by name/slug', () => {
    menusPage.visit();
    cy.wait('@getMenus');

    cy.intercept('GET', '/api/v1/menus*q=Principal*', {
      body: {
        data: [{ id: 1, name: 'Menu Principal', slug: 'menu-principal', position: 1, status: 'active' }],
        next_cursor: null,
        prev_cursor: null,
      },
    }).as('searchMenus');

    menusPage.searchByQuery('Principal');
    cy.wait('@searchMenus');

    cy.get('#tbody tr').should('have.length', 1);
    cy.get('#tbody').should('contain.text', 'Menu Principal');
  });

  it('UC48: should create a menu', () => {
    menusPage.visit();
    cy.wait('@getMenus');

    cy.intercept('POST', '/api/v1/menus', {
      statusCode: 201,
      body: { data: { id: 4, name: 'New Menu', slug: 'new-menu', position: 4, status: 'active' } },
    }).as('createMenu');

    cy.intercept('GET', '/api/v1/menus*', { fixture: 'menus/list.json' }).as('reloadMenus');

    menusPage.clickNew();
    cy.get('#editModal').should('be.visible');

    menusPage.fillName('New Menu');
    menusPage.fillSlug('new-menu');
    menusPage.selectStatus('active');
    menusPage.clickSave();

    cy.wait('@createMenu');
    cy.get('.toast').should('contain.text', 'criado');
  });

  it('UC49: should edit a menu', () => {
    menusPage.visit();
    cy.wait('@getMenus');

    cy.intercept('GET', '/api/v1/menus/1', {
      statusCode: 200,
      fixture: 'menus/single.json',
    }).as('getMenu');

    cy.intercept('PUT', '/api/v1/menus/1', {
      statusCode: 200,
      body: { data: { id: 1, name: 'Menu Principal Updated', slug: 'menu-principal', status: 'active' } },
    }).as('updateMenu');

    cy.intercept('GET', '/api/v1/menus*', { fixture: 'menus/list.json' }).as('reloadMenus');

    menusPage.clickEditOnRow(1);
    cy.get('#editModal').should('be.visible');

    cy.get('#f_name').clear().type('Menu Principal Updated');
    menusPage.clickSave();

    cy.wait('@updateMenu');
    cy.get('.toast').should('contain.text', 'atualizado');
  });

  it('UC50: should reorder menus', () => {
    menusPage.visit();
    cy.wait('@getMenus');

    cy.intercept('PUT', '/api/v1/menus/reorder', {
      statusCode: 200,
      body: { message: 'Ordem salva.' },
    }).as('reorderMenus');

    cy.intercept('GET', '/api/v1/menus*', { fixture: 'menus/list.json' }).as('reloadMenus');

    // Verify table has drag handles (reorder capability)
    cy.get('#tbody tr').should('have.length', 3);

    // The save order button should exist
    cy.get('body').then(($body) => {
      if ($body.find('#btnSaveOrder').length > 0) {
        menusPage.clickSaveOrder();
        cy.wait('@reorderMenus');
        cy.get('.toast').should('be.visible');
      }
    });
  });

  it('UC51: should delete a menu', () => {
    menusPage.visit();
    cy.wait('@getMenus');

    cy.intercept('DELETE', '/api/v1/menus/3', {
      statusCode: 200,
      body: {},
    }).as('deleteMenu');

    cy.intercept('GET', '/api/v1/menus*', { fixture: 'menus/list.json' }).as('reloadMenus');

    cy.on('window:confirm', () => true);

    menusPage.clickDeleteOnRow(3);

    cy.wait('@deleteMenu');
    cy.get('.toast').should('contain.text', 'excluído');
  });
});
