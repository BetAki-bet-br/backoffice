import DashboardPage from '../../support/page-objects/DashboardPage';

describe('Dashboard', () => {
  const dashboardPage = new DashboardPage();

  beforeEach(() => {
    cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
  });

  it('UC7: should display KPI cards on the dashboard', () => {
    dashboardPage.visit();

    // Dashboard should have informational content
    cy.get('.content-main').should('be.visible');
    cy.contains('Dashboard').should('be.visible');
  });

  it('UC8: should show sidebar with navigation links', () => {
    dashboardPage.visit();

    dashboardPage.getSidebar().should('be.visible');

    // Verify key navigation links exist
    dashboardPage.getSidebar().contains('Dashboard').should('exist');
    dashboardPage.getSidebar().contains('Banners').should('exist');
    dashboardPage.getSidebar().contains('Slots').should('exist');
    dashboardPage.getSidebar().contains('Categorias').should('exist');
    dashboardPage.getSidebar().contains('Menus').should('exist');
    dashboardPage.getSidebar().contains('Footer').should('exist');
    dashboardPage.getSidebar().contains('Usuários').should('exist');
  });

  it('UC9: should show user name in topbar and have logout button', () => {
    dashboardPage.visit();

    dashboardPage.getTopbar().should('be.visible');
    dashboardPage.verifyUserName('Admin Test');

    cy.get('#logoutBtn').should('be.visible').and('contain.text', 'Sair');
  });
});
