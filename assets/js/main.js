// Funções utilitárias
const Utils = {
    // Formatar moeda
    formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },
    
    // Formatar data
    formatDate(date) {
        return new Date(date).toLocaleString('pt-BR');
    },
    
    // Confirmar ação
    confirm(message) {
        return window.confirm(message);
    },
    
    // Mostrar alerta
    showAlert(message, type = 'success') {
        const container = document.querySelector('.alert-container') || this.createAlertContainer();
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(alert);
        
        setTimeout(() => alert.remove(), 5000);
    },
    
    createAlertContainer() {
        const container = document.createElement('div');
        container.className = 'alert-container';
        document.body.appendChild(container);
        return container;
    },
    
    // Validar CPF
    validateCPF(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');
        if (cpf.length !== 11) return false;
        
        let sum = 0;
        let remainder;
        
        for (let i = 1; i <= 9; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(9, 10))) return false;
        
        sum = 0;
        for (let i = 1; i <= 10; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(10, 11))) return false;
        
        return true;
    },
    
    // Validar CNPJ
    validateCNPJ(cnpj) {
        cnpj = cnpj.replace(/[^\d]/g, '');
        if (cnpj.length !== 14) return false;
        
        let size = cnpj.length - 2;
        let numbers = cnpj.substring(0, size);
        let digits = cnpj.substring(size);
        let sum = 0;
        let pos = size - 7;
        
        for (let i = size; i >= 1; i--) {
            sum += numbers.charAt(size - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        if (result != digits.charAt(0)) return false;
        
        size = size + 1;
        numbers = cnpj.substring(0, size);
        sum = 0;
        pos = size - 7;
        
        for (let i = size; i >= 1; i--) {
            sum += numbers.charAt(size - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        if (result != digits.charAt(1)) return false;
        
        return true;
    },
    
    // Máscaras
    maskCPF(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})/, '$1-$2')
            .replace(/(-\d{2})\d+?$/, '$1');
    },
    
    maskCNPJ(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2')
            .replace(/(-\d{2})\d+?$/, '$1');
    },
    
    maskPhone(value) {
        value = value.replace(/\D/g, '');
        if (value.length <= 10) {
            return value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else {
            return value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }
    },
    
    maskMoney(value) {
        value = value.replace(/\D/g, '');
        value = (parseFloat(value) / 100).toFixed(2);
        return value.replace('.', ',');
    }
};

// Tabs
document.addEventListener('DOMContentLoaded', function() {
    // Gerenciar tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Remover active de todos
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Adicionar active no clicado
            this.classList.add('active');
            document.getElementById(tabId)?.classList.add('active');
        });
    });
    
    // Auto-remover alertas após 5 segundos
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
    
    // Aplicar máscaras automaticamente
    document.querySelectorAll('input[data-mask="cpf"]').forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = Utils.maskCPF(e.target.value);
        });
    });
    
    document.querySelectorAll('input[data-mask="cnpj"]').forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = Utils.maskCNPJ(e.target.value);
        });
    });
    
    document.querySelectorAll('input[data-mask="phone"]').forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = Utils.maskPhone(e.target.value);
        });
    });
});

// Modal
const Modal = {
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
    },
    
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }
};

// Toggle Sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;
    
    // Adicionar classe ao body para indicar presença da sidebar
    if (sidebar) {
        body.classList.add('has-sidebar');
        
        // Verificar se existe estado salvo (apenas para desktop)
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        if (window.innerWidth > 1024 && sidebarState === 'true') {
            sidebar.classList.add('is-collapsed');
            body.classList.add('sidebar-collapsed');
        }
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 1024) {
                    // Mobile/Tablet: Apenas fecha (recolhe)
                    body.classList.remove('sidebar-open');
                } else {
                    // Desktop: Toggle do modo colapsado
                    sidebar.classList.toggle('is-collapsed');
                    body.classList.toggle('sidebar-collapsed');

                    // Salvar estado
                    const isCollapsed = sidebar.classList.contains('is-collapsed');
                    localStorage.setItem('sidebarCollapsed', isCollapsed);
                }
            });
        }
    }

    // Funcionalidade do botão de menu mobile
    // Vamos criar dinamicamente se não existir, ou procurar se existir
    let mobileMenuBtn = document.getElementById('mobileMenuBtn');

    // Se não tiver na navbar, vamos tentar injetar
    if (!mobileMenuBtn && document.querySelector('.navbar')) {
        const navbar = document.querySelector('.navbar');
        const brand = navbar.querySelector('.navbar-brand');

        if (brand) {
            mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.id = 'mobileMenuBtn';
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            mobileMenuBtn.className = 'btn-icon'; // Uma classe simples
            mobileMenuBtn.style.background = 'none';
            mobileMenuBtn.style.border = 'none';
            mobileMenuBtn.style.fontSize = '24px';
            mobileMenuBtn.style.color = 'var(--primary-color)';
            mobileMenuBtn.style.marginRight = '15px';
            mobileMenuBtn.style.cursor = 'pointer';

            // Inserir antes da brand
            navbar.insertBefore(mobileMenuBtn, brand);

            // Só mostrar em mobile
            const style = document.createElement('style');
            style.innerHTML = `
                #mobileMenuBtn { display: none; }
                @media (max-width: 1024px) {
                    #mobileMenuBtn { display: block; }
                }
            `;
            document.head.appendChild(style);
        }
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            body.classList.toggle('sidebar-open');
        });
    }

    // Fechar sidebar ao clicar fora (overlay) no mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024 && body.classList.contains('sidebar-open')) {
            // Se clicar fora da sidebar e fora do botão de menu
            if (!e.target.closest('.sidebar') && !e.target.closest('#mobileMenuBtn')) {
                body.classList.remove('sidebar-open');
            }
        }
    });

    // Ajustar classes ao redimensionar a janela
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            body.classList.remove('sidebar-open');
            // Restaurar estado salvo
            const sidebarState = localStorage.getItem('sidebarCollapsed');
            if (sidebarState === 'true') {
                sidebar?.classList.add('is-collapsed');
                body.classList.add('sidebar-collapsed');
            } else {
                sidebar?.classList.remove('is-collapsed');
                body.classList.remove('sidebar-collapsed');
            }
        } else {
            // Em mobile, remove classes de collapse (visual) e usa sidebar-open
            sidebar?.classList.remove('is-collapsed');
            body.classList.remove('sidebar-collapsed');
        }
    });
});

// Exportar para uso global
window.Utils = Utils;
window.Modal = Modal;
