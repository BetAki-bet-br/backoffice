export default class BasePage {
  constructor(path) {
    this.path = path;
  }

  visit() {
    cy.visitAuthenticated(this.path);
    return this;
  }

  verifyUrl() {
    cy.url().should('include', this.path);
    return this;
  }

  // --- Sidebar ---

  getSidebar() {
    return cy.get('#sidebar');
  }

  clickSidebarLink(text) {
    this.getSidebar().contains('.sidebar-link', text).click();
    return this;
  }

  verifySidebarActive(text) {
    this.getSidebar().contains('.sidebar-link', text).should('have.class', 'active');
    return this;
  }

  // --- Topbar ---

  getTopbar() {
    return cy.get('.topbar');
  }

  verifyUserName(name) {
    cy.get('#currentUserNameTop').should('contain.text', name);
    return this;
  }

  clickLogout() {
    cy.get('#logoutBtn').click();
    return this;
  }

  // --- Table ---

  getTable() {
    return cy.get('table');
  }

  getTableRows() {
    return cy.get('table tbody tr');
  }

  getTableRowByIndex(i) {
    return this.getTableRows().eq(i);
  }

  verifyTableHasRows() {
    this.getTableRows().should('have.length.greaterThan', 0);
    return this;
  }

  verifyTableEmpty() {
    this.getTableRows().should('have.length', 0);
    return this;
  }

  // --- Modals ---

  getModal() {
    return cy.get('.modal.show');
  }

  openCreateModal() {
    cy.contains('button', /Novo|Criar|Adicionar|New/i).click();
    this.getModal().should('be.visible');
    return this;
  }

  closeModal() {
    this.getModal().find('[data-bs-dismiss="modal"]').first().click();
    return this;
  }

  submitModal() {
    this.getModal().find('button[type="submit"], .btn-primary').last().click();
    return this;
  }

  // --- Filters ---

  searchBy(text) {
    cy.get('input[name="q"], input[placeholder*="Buscar"], input[placeholder*="buscar"], input[placeholder*="Search"]').first().clear().type(text);
    cy.contains('button', /Buscar|Filtrar|Search|Aplicar/i).first().click();
    return this;
  }

  filterByStatus(status) {
    cy.get('select').filter('[name*="status"], [id*="status"]').first().select(status);
    return this;
  }

  clearFilters() {
    cy.contains('button', /Limpar|Clear|Reset/i).click();
    return this;
  }

  // --- Pagination ---

  clickNextPage() {
    cy.contains('button', /Próxim|Next|›/i).click();
    return this;
  }

  clickPrevPage() {
    cy.contains('button', /Anterior|Prev|‹/i).click();
    return this;
  }

  // --- Toast ---

  verifyToast(message, type = 'success') {
    cy.get('.toast').should('be.visible').and('contain.text', message);
    return this;
  }

  // --- Row Actions ---

  clickRowAction(rowIndex, actionText) {
    this.getTableRowByIndex(rowIndex).contains('button, a', actionText).click();
    return this;
  }
}
