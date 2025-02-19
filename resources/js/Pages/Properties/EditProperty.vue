<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, useForm, Head } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch, defineProps, computed } from 'vue';
import { CheckCircleIcon, XMarkIcon, ExclamationTriangleIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import axios from 'axios';

const props = defineProps({
    property: {
        type: Object,
        required: true,
    },
    typeOwners: {
        type: Array,
        required: true,
    },
    users: {
        type: Array,
        required: true,
    },
    owners: {
        type: Array,
        required: true,
    },
    documents: {
        type: Array,
        required: true,
    },
});

// Configuração do Alerta
const alert = reactive({
    show: false,
    message: '',
    type: '',
    color: '',
});

const owners = ref(props.owners || []); // Proprietários da propriedade
const users = ref(props.users || []); // Usuários disponíveis para adicionar como proprietários
const documents = ref(props.documents || []); // Documento a ser adicionado

const docDate = ref(false);

// Classes dinâmicas para cores de alertas
const colors = {
    red: "bg-red-200 text-red-800",
    green: "bg-green-200 text-green-800",
    blue: "bg-blue-200 text-blue-800",
    yellow: "bg-yellow-100 text-yellow-800",
    gray: "bg-gray-200 text-gray-800",
};

const alertClass = computed(() => colors[alert.color] || "bg-gray-200 text-gray-800");

// Funções referentes ao Proprietário ******************************************************************************
const showModalOwner = ref(false); // Mostra o modal de adicionar proprietário
const searchTerm = ref(""); // Termo de busca para proprietários
const selectedOwner = ref({ id: null, name: "", cpf_cnpj: "", percent: "", type_ownership: "", observations: "" }); // Proprietário selecionado
const filteredUsers = ref([]); // Usuários filtrados pela busca
const ownersToDelete = ref([]); // Lista de IDs dos proprietários removidos
const documentsToDelete = ref([]); // Lista de IDs dos documentos removidos

// Função para buscar proprietários pelo CPF/CNPJ
const searchOwners = () => {
    if (searchTerm.value.length > 10) {
        filteredUsers.value = users.value.filter((user) =>
            user.cpf_cnpj.includes(searchTerm.value) || user.name.toLowerCase().includes(searchTerm.value.toLowerCase())
        );
    } else {
        filteredUsers.value = [];
    }
};

// Selecionar um proprietário na busca
const selectOwner = (user) => {
    selectedOwner.value = { ...user, percent: "", observations: "" };
    filteredUsers.value = [];
    searchTerm.value = user.name + ' - ' + applyCpfCnpjMask(user.cpf_cnpj); // Atualiza o campo de busca com o nome do selecionado
};

const clearOwner = () => {
    selectedOwner.value = { id: null, name: "", cpf_cnpj: "", percent: "", type_ownership: "", observations: "" };
    searchTerm.value = "";
};

// Adicionar o proprietário à lista
const addOwner = async (event) => {
    if (event) {
        event.preventDefault(); // Previne o envio do formulário
        event.stopPropagation(); // Previne a propagação do evento
    }

    const ownerSave = [];
    const ownerTest = props.property.owners.forEach( (owner) => {
        ownerSave.push({
            id: owner.id,
            user_id: owner.user_id,
            type_ownership_id: owner.type_ownership_id,
            percentage: owner.percentage,
            observations: owner.observations,
        })
    });    

    if (!selectedOwner.value.id || !selectedOwner.value.percent || !selectedOwner.value.type_ownership) { // Verifica se os campos obrigatórios estão preenchidos
        alert.message = "Por favor, preencha todos os campos do proprietário.";
        alert.show = true;
        alert.type = "error";
        alert.color = "red";
        setTimeout(() => {
            alert.show = false;
        }, 4000);
        return;
    }

    // Converte os valores para os tipos corretos
    const ownerId = parseInt(selectedOwner.value.id); // Converte o ID para número
    
    // Converte os valores para os tipos corretos
    const enteredPercent = parseFloat(selectedOwner.value.percent); // Converte o percentual para número
    const typeOwnership = props.typeOwners.find(type => type.id === parseInt(selectedOwner.value.type_ownership)); // Encontra o tipo de propriedade pelo ID

    // Verifica se o percentual inserido é válido
    if (isNaN(enteredPercent) || enteredPercent <= 0) {
        alert.message = "O percentual deve ser um valor numérico positivo.";
        alert.show = true;
        alert.type = "error";
        alert.color = "red";
        setTimeout(() => { alert.show = false; }, 2000);
        return;
    }

    // Verifica se o proprietário já está na lista
    const ownerExists = owners.value.find((owner) => owner.user.id === ownerId);

    if (ownerExists) {
        alert.message = "Este proprietário já está na lista inicial.";
        alert.show = true;
        alert.type = "warning";
        alert.color = "yellow";
        setTimeout(() => { alert.show = false; }, 2000);
        return;
    }

    // Verifica se o proprietário selecionado já existe na lista com o mesmo tipo de propriedade
    const existingOwnerWithSameType = owners.value.find((owner) => owner.user_id === ownerId && owner.type_ownership.id === typeOwnership.id
        // (owner) => owner.user.id === ownerId && owner.type_ownership.id === typeOwnership.id
    );

    if (existingOwnerWithSameType) {
        alert.message = "Este proprietário já está na lista com o mesmo tipo de propriedade.";
        alert.show = true;
        alert.type = "warning";
        alert.color = "yellow";
        setTimeout(() => { alert.show = false; }, 2000);
        return;
    }

    // ** Obtém proprietários do banco e do Vue (ainda não salvos) **
    const existingOwners = props.property.owners || []; // Proprietários salvos no banco
    const newOwners = owners.value; // Proprietários adicionados no Vue

    // ** Calcula o total utilizado para cada type_ownership **
    const totalPercentByType = [...existingOwners, ...newOwners]
        .filter(owner => owner.type_ownership === typeOwnership)
        .reduce((sum, owner) => sum + parseFloat(owner.percent), 0);

    // ** Calcula o percentual disponível **
    const availablePercentByType = 100 - totalPercentByType;

    // ** Verifica se o percentual escolhido excede o disponível para type_ownership_id === 1 **
    if (typeOwnership.id === 1) {
        const totalProprietarioPercentFromDB = existingOwners
            .filter(owner => owner.type_ownership_id === 1)
            .reduce((sum, owner) => sum + parseFloat(owner.percentage), 0);

        const totalProprietarioPercentFromVue = newOwners
            .filter(owner => owner.type_ownership.id === 1)
            .reduce((sum, owner) => sum + parseFloat(owner.percentage), 0);

        const totalProprietarioPercent = totalProprietarioPercentFromDB + totalProprietarioPercentFromVue + enteredPercent;

        if (totalProprietarioPercent > 100) {
            alert.message = "A soma dos percentuais dos proprietários não pode exceder 100%.";
            alert.show = true;
            alert.type = "error";
            alert.color = "red";
            setTimeout(() => { alert.show = false; }, 4000);
            return false;
        }
    } else {
        // ** Verifica se o percentual escolhido excede o disponível para type_ownership_id !== 1 **
        const totalOtherPercentFromDB = existingOwners
            .filter(owner => owner.type_ownership_id !== 1)
            .reduce((sum, owner) => sum + parseFloat(owner.percentage), 0);

        const totalOtherPercentFromVue = newOwners
            .filter(owner => owner.type_ownership.id !== 1)
            .reduce((sum, owner) => sum + parseFloat(owner.percentage), 0);

        const totalOtherPercent = totalOtherPercentFromDB + enteredPercent;
        
        console.log(totalOtherPercent, "Verificar aqui");
        if (totalOtherPercent > 100) {
            alert.message = "A soma dos percentuais das outras opções não pode exceder 100%.";
            alert.show = true;
            alert.type = "error";
            alert.color = "red";
            setTimeout(() => { alert.show = false; }, 4000);
            return false;
        }
    }

    // Adiciona corretamente o proprietário
    owners.value.push({
        user: {
            id: selectedOwner.value.id,
            // id: null,
            name: selectedOwner.value.name,
            cpf_cnpj: selectedOwner.value.cpf_cnpj,
        },
        percentage: enteredPercent,
        type_ownership: {
            id: typeOwnership.id, // Aqui agora temos certeza que estamos passando um ID válido
            name: typeOwnership.name,
        },
        user_id: selectedOwner.value.id, // Adicionando explicitamente esse campo
        type_ownership_id: typeOwnership.id, // Adicionando explicitamente esse campo
        observations: selectedOwner.value.observations || '',
    });

    form.owners = owners.value;

    // Atualiza o formulário para envio
    form.owners = [...owners.value];

    clearOwner();
    searchTerm.value = "";
    showModalOwner.value = false;

    alert.message = "Proprietário adicionado com sucesso.";
    alert.show = true;
    alert.type = "success";
    alert.color = "green";
    setTimeout(() => { alert.show = false; }, 2000);
};

const outModalOwner = () => {
    clearOwner();
    showModalOwner.value = false;
    searchTerm.value = "";
};

// Remover proprietário da lista
const removeOwner = (index) => {
    if (index !== -1) {
        const removedOwner = owners.value[index]; // Obtém o item que será removido

        // Marca para remoção no backend se o proprietário já existia
        if (removedOwner.id) {
            ownersToDelete.value.push(removedOwner.id);
        }

        // Remove do array local
        owners.value.splice(index, 1);
        form.owners.splice(index, 1);
    }
};

// Inicializa o formulário com os dados da propriedade existente********************
const form = useForm({
    is_active: props.property.is_active,
    type_property: props.property.type_property,
    title_deed: props.property.title_deed,
    title_deed_number: props.property.title_deed_number,
    other: props.property.other,
    area: props.property.area,
    unit: props.property.unit,
    address: props.property.address,
    city: props.property.city,
    city_id: props.property.city_id,
    district: props.property.district,
    locality: props.property.locality,
    nickname: props.property.nickname,
    about: props.property.about,
    file_photo: props.property.file_photo,
    documents: props.property.documents || [],
    owners: props.owners || [],
});

const sanitizeValue = (value) => {
    if (!value) return ""; // Retorna uma string vazia se o valor for null ou undefined
    return String(value).replace(/\D/g, ''); // Garante que o valor seja tratado como string
};

// Função para enviar o formulário de atualização
const updateForm = () => {
    
    // Formatando os proprietários corretamente
    form.owners = form.owners.map(owner => 
    {
        return {
            // id: owner.id ? parseInt(owner.id) : null, // Certifique-se de que o ID é um número válido
            id: null, // Certifique-se de que o ID é um número válido
            user_id: owner.user_id ? parseInt(owner.user_id) : null, // Certifique-se de que o ID do usuário é um número válido  
            type_ownership_id: owner.type_ownership_id ? parseInt(owner.type_ownership_id) : null, 
            percentage: owner.percentage ? parseFloat(owner.percentage) : null,  
            observations: owner.observations || null,
        };
    });

    form.documents = form.documents.map(document => {
        return {
            name: document.name,
            date: document.date,
            show: document.show,
            file: document.file,
            file_name: document.file_name,
        };
    });

    console.log(form.documents, "Proprietários");
    
    form.is_active = form.is_active === "Propriedade Ativa" ? 1 : 0;

    if (form.owners.length === 0) {
        form.owners = props.property.owners;
    }

    if (!form.owners || form.owners.length === 0) {
        alert.message = "Deve ser adicionado no mínimo 1 proprietário à propriedade.";
        alert.show = true;
        alert.type = "error";
        alert.color = "red";
        setTimeout(() => { alert.show = false; }, 4000);
        return;
    }

    form.put(`/property/${props.property.id}`, {
        onSuccess: () => {
            alert.message = "Propriedade atualizada com sucesso!";
            alert.show = true;
            alert.type = "success";
            alert.color = "green";
            setTimeout(() => { alert.show = false; }, 3000);
        },
        onError: (errors) => {
            console.error('Erro ao atualizar a propriedade', errors);
            alert.message = "Erro ao atualizar a propriedade.";
            alert.show = true;
            alert.type = "error";
            alert.color = "red";
            setTimeout(() => { alert.show = false; }, 3000);
        }
    });
};

// Converter arquivo para Base64
const convertToBase64 = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = (error) => reject(error);
        });
    
};

// Captura e converte a imagem principal para Base64
const handleFileChange = async (event) => {
    if (!event || !event.target || !event.target.files) return;
    const file = event.target.files[0];
    if (file) {
        form.file_photo = await convertToBase64(file);
    }
};

//Funções referentes aos documentos ****************************************************
// const documents = reactive([]);

const isAdding = ref(false); // Controle para evitar chamadas duplicadas
const addDocument = () => {

    if (isAdding.value) return; // Se já está adicionando, impede outra chamada
    isAdding.value = true; // Bloqueia novas execuções enquanto processa

    if (!newDocument.value.file) {
        alert.message = "Por favor, selecione um arquivo válido antes de adicionar.";
        alert.show = true;
        alert.type = "warning";
        alert.color = "yellow";
        setTimeout(() => {
                alert.show = false;
            }, 3000);
        isAdding.value = false; // Libera novamente para nova tentativa
        return;
    }

    const documentDate = newDocument.value.date ? newDocument.value.date : null; // Define uma data padrão se não informada

    documents.value.push({
        name: newDocument.value.name,
        date: documentDate,
        show: newDocument.value.show,
        file: newDocument.value.file,
        file_name: newDocument.value.file_name,
    });
    newDocument.value = {
        name: '',
        date: '',
        show: true,
        file: null,
        file_name: '',
    };

    console.log(documents, "Documentos");
    form.documents = documents;
    showModal.value = false;

    isAdding.value = false; // Libera para nova adição após finalizar
    resetFileInput();
};

const showModal = ref(false);
const newDocument = ref({
    name: '',
    date: '',
    show: true,
    file_name: '',
    file: null,
});

// Captura e converte os documentos para Base64
const handleDocumentUpload = async (event) => {
    if (!event || !event.target || !event.target.files || event.target.files.length === 0) return;

    const file = event.target.files[0];

    // Verifica se o arquivo é um tipo permitido
    const allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.google-earth.kml+xml',
        'application/vnd.google-earth.kmz',
        'application/octet-stream', // Alguns navegadores identificam .KMZ assim
        'application/zip', // Algumas vezes .KMZ é identificado como ZIP
        
]     
    console.log("Tipo do arquivo detectado:", !allowedTypes.includes(file.type));
    if (!allowedTypes.includes(file.type)) {
        alert.message = "Por favor, selecione um arquivo PDF ou Word (.pdf, .doc, .docx, .kmz, .kml)";
        alert.show = true;
        alert.type = "warning";
        alert.color = "yellow";
        setTimeout(() => {
            alert.show = false;
        }, 3000);
        event.target.value = ""; // Limpa o input
        return;
    }
    

    // Define os valores no estado reativo do Vue de forma controlada
    newDocument.value.file_name = file.name;
    console.log(newDocument.value,file);
    try {
        newDocument.value.file = await convertToBase64(file);

        // // Aguarda o Vue atualizar a reatividade antes de chamar `addDocument`
        // await nextTick();

        // addDocument();  // Agora garantimos que a função é chamada apenas quando há um arquivo válido
    } catch (error) {
        console.error("Erro ao converter arquivo para Base64:", error);
    }
};

const inputKey = ref(0);

const resetFileInput = () => {
    inputKey.value += 1;  // Isso força o Vue a recriar o input e impedir eventos duplicados
};

const removeDocument = (index, ) => {
    if (index !== -1) {
        const removedDocument = documents.value[index]; // Obtém o item que será removido

        // Marca para remoção no backend se o proprietário já existia
        if (removedDocument.id) {
            documentsToDelete.value.push(removedDocument.id);
        }

        // Remove do array local
        documents.value.splice(index, 1);
        form.documents.splice(index, 1);
    }
};

const selectedOption = ref(null);

// Controle para cidades ***********************************************************************************************
const allCities = ref([]);
const filteredCities = ref([]);
const isLoadingCities = ref(false);
const showSuggestions = ref(false);

// Busca inicial de todas as cidades
onMounted(async () => {
    try {
        isLoadingCities.value = true;
        const response = await axios.get('https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome');
        allCities.value = response.data.map(city => ({
            id: city.id,
            nome: `${city.nome} / ${city.microrregiao.mesorregiao.UF.sigla}`
        }));
    } catch (error) {
        console.error('Erro ao buscar cidades:', error);
    } finally {
        isLoadingCities.value = false;
    }
});

// Função para filtrar cidades localmente
const filterCities = (query) => {
    if (query.length >= 3) {
        filteredCities.value = allCities.value.filter((city) =>
            city.nome.toLowerCase().includes(query.toLowerCase())
        );
        showSuggestions.value = filteredCities.value.length > 0;
    } else {
        filteredCities.value = [];
        showSuggestions.value = false;
    }
};

// Observa mudanças no campo de cidade
watch(
    () => form.city,
    (newCity) => {
        filterCities(newCity);
    }
);

// Fechar sugestões com atraso seguro
const closeSuggestions = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200); // Timeout para permitir clique nas sugestões
};

// Função para aplicar a máscara de CPF ou CNPJ ***********************************************************************
const applyCpfCnpjMask = (value) => {
    const numericValue = value.replace(/\D/g, '');

    if (numericValue.length <= 11) {
        // CPF: 000.000.000-00
        return numericValue
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
        // CNPJ: 00.000.000/0000-00
        return numericValue
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }

};

// Observa mudanças no valor do CPF ou CNPJ e aplica a máscara
watch(
    () => form.cpf_cnpj,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue) {
            form.cpf_cnpj = applyCpfCnpjMask(newValue);
        }
    }
);

//Observa mudanças no valor do CPF ou CNPJ dos usuários e aplica a máscara
watch(
    () => users.value,
    (newUsers) => {
        newUsers.forEach(user => {
            user.cpf_cnpj = applyCpfCnpjMask(user.cpf_cnpj);
        });
    },
    { deep: true }
);

// Observa mudanças no valor do CPF ou CNPJ do proprietário selecionado e aplica a máscara
watch(
    () => selectedOwner.value.cpf_cnpj,
    (newValue, oldValue) => {
        if (newValue && newValue !== oldValue) {
            selectedOwner.value.cpf_cnpj = applyCpfCnpjMask(newValue);
        }
    }
);
</script>

<template>
    <Head title="Editar Propriedade" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Editar Propriedade
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="bg-white rounded-lg shadow p-8">
                            <form @submit.prevent="submitForm" enctype="multipart/form-data">
                                <div class="space-y-12">
                                    <div class="border-b border-gray-900/10 pb-12">
                                        <h2 class="text-base/7 font-semibold text-gray-900">Cadastro de Propriedade</h2>
                                        <p class="mt-1 text-sm/6 text-gray-600">Este formulário será utilizado para registrar as informações da propriedade.</p>
                                        <div class="flex justify-end">
                                            <!-- <input type="radio" class="p-2 mt-2" v-model="selectedOption" value="1" />
                                            <label for="1" class="p-2">Pessoa Física</label> -->
                                            <label class="inline-flex items-center mb-5 cursor-pointer">
                                                <input type="checkbox" v-model="form.is_active" class="sr-only peer" :checked="form.is_active === 1">
                                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-700">{{ form.is_active === 1 ? "Propriedade Ativa" : "Propriedade Inativa"}}</span>
                                            </label>
                                        </div>
                                        
                                        
                                        <div class="mt-6 mb-12 grid grid-cols-1 gap-4 ">
                                            <!-- Linha do botão -->
                                            <div class="flex justify-start">
                                                <button @click="showModalOwner = true"
                                                    class="rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-gray-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                                    Adicionar Proprietário
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="size-5 inline-block ml-2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Linha da tabela -->
                                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                                                <table
                                                    class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                                    <thead
                                                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-600 dark:text-gray-400">
                                                        <tr>
                                                            <th class="px-6 py-3">Nome</th>
                                                            <th class="px-6 py-3">CPF/CNPJ</th>
                                                            <th class="px-6 py-3">Percentual</th>
                                                            <th class="px-6 py-3">Relação</th>
                                                            <th class="px-6 py-3">Notas</th>
                                                            <th class="px-6 py-3">Ações</th>
                                                            
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(owner, index) in owners" :key="index"
                                                            class="bg-white border-b dark:bg-gray-700 dark:border-gray-500">
                                                            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.user.name }}</td>
                                                            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ applyCpfCnpjMask(owner.user.cpf_cnpj) }}</td>
                                                            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.percentage }}%</td>
                                                            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.type_ownership.name  }}</td>
                                                            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ owner.observations }}</td>
                                                            <td class="px-6 py-4">
                                                                <button type="button" @click="removeOwner(index, owner.id)" class="bg-red-500 border border-gray-600 rounded p-1 text-sm font-semibold text-white hover:bg-red-700">
                                                                    Excluir
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="mb-8 grid grid-cols-1 sm:grid-cols-12 gap-x-6 gap-y-8">
                                            <div class="sm:col-span-2 col-span-full">
                                                <label for="type_property"
                                                    class="block text-sm font-medium text-gray-900">Tipo de Propriedade</label>
                                                <div class="mt-2">
                                                    <select id="type_property" name="type_property" v-model.number="form.type_property" required
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm">
                                                        <option value="1">Urbana</option>
                                                        <option value="2">Rural</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-3 col-span-full">
                                                <label for="title_deed"
                                                    class="block text-sm font-medium text-gray-900">Título de Propriedade</label>
                                                <div class="mt-2">
                                                    <select id="title_deed" name="title_deed" v-model.number="form.title_deed" required
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm">
                                                        <option value="1">Matrícula</option>
                                                        <option value="2">Transcrição</option>
                                                        <option value="3">Posse</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-4 col-span-full">
                                                <label for="title" class="block text-sm font-medium text-gray-900">
                                                    <template v-if="form.title_deed === null">Inscrição</template>
                                                    <template v-else-if="form.title_deed === 1">Nº
                                                        Matrícula</template>
                                                    <template v-else-if="form.title_deed === 2">Nº
                                                        Transcrição</template>
                                                    <template v-else>Descrição da Posse</template>
                                                </label>
                                                <div v-if="form.title_deed != 1" class="mt-2">
                                                    <input type="text" name="title" id="title"
                                                        v-model="form.title_deed_number"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm" />
                                                </div>
                                                <div v-else-if="form.title_deed === 1" class="mt-2 flex items-center w-full  text-white rounded-lg shadow-sm ">
                                                    <div class="relative w-full">
                                                        <input type="text" v-model="form.title_deed_number" placeholder="Digite a Matrícula"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm rounded-r-none border-r-0" />
                                                    </div>
                                                    <div class="relative w-full">
                                                        <input type="text" v-model="form.other" placeholder="Cartório"
                                                        class="block w-full bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm rounded-r-lg rounded-l-none border-l " />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-3 col-span-full">
                                                <label for="area" class="block text-sm font-medium text-gray-900">Área</label>
                                                <div class="mt-2 flex items-center w-full  text-white rounded-lg shadow-sm ">
                                                    <div class="relative w-full">
                                                        <input type="text" v-model="form.area" placeholder="Digite a área"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm rounded-r-none border border-r-0 border-2" />
                                                    </div>
                                                    <div class="relative w-full">
                                                        <select v-model="form.unit" class="block w-full bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm rounded-r-lg cursor-pointer border-l pr-10">
                                                            <option value="" disabled>Unidade</option>
                                                            <option value="m2">m²</option>
                                                            <option value="ha">Ha</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-6 col-span-full">
                                                <label for="address" class="block text-sm font-medium text-gray-900">Endereço</label>
                                                <div class="mt-2">
                                                    <input type="text" name="address" id="address" v-model="form.address"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm" />
                                                </div>
                                            </div>

                                            <div class="sm:col-span-6 col-span-full">
                                                <label for="city" class="block text-sm font-medium text-gray-900">Município/Estado
                                                </label>
                                                <div class="mt-2">
                                                    <input id="city" type="text" v-model="form.city"
                                                        @focus="showSuggestions = true" @blur="closeSuggestions" placeholder="Digite o nome da cidade"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm" />
                                                    <div v-if="showSuggestions"
                                                        class="absolute z-10 w-1/4 mt-1 bg-white rounded-md shadow-lg">
                                                        <ul class="py-1">
                                                            <li v-for="city in filteredCities" :key="city.id" @click="{
                                                                form.city = city.nome;
                                                                form.city_id = city.id;
                                                                showSuggestions = false;
                                                            }"
                                                                class="px-3 py-1.5 text-base text-gray-900 cursor-pointer hover:bg-gray-100">
                                                                {{ city.nome }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-4 col-span-full">
                                                <label for="district" class="block text-sm font-medium text-gray-900">Distrito</label>
                                                <div class="mt-2">
                                                    <input type="text" name="district" id="district" v-model="form.district"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm" />
                                                </div>
                                            </div>

                                            <div class="sm:col-span-4 col-span-full">
                                                <label for="locality" class="block text-sm font-medium text-gray-900">{{form.type_property === 1 ? 'Bairro' : 'Localidade'}}</label>
                                                <div class="mt-2">
                                                    <input type="text" name="locality" id="locality" v-model="form.locality"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm" />
                                                </div>
                                            </div>

                                            <div class="sm:col-span-4 col-span-full">
                                                <label for="nickname" class="block text-sm font-medium text-gray-900">Apelido</label>
                                                <div class="mt-2">
                                                    <input type="text" name="nickname" id="nickname" v-model="form.nickname"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm" />
                                                </div>
                                            </div>

                                            <div class="col-span-full">
                                                <label for="about" class="block text-sm font-medium text-gray-900">Observações</label>
                                                <div class="mt-2">
                                                    <textarea name="about" id="about" rows="3" v-model="form.about"
                                                        class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="border-b border-gray-900/10 pb-12">
                                        <label for="document-photo" class="block text-sm font-medium text-gray-700">Trocar Foto da Propriedade</label>
                                        <input type="file" id="document-photo" @change="handleFileChange" accept="image/*" 
                                            class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div class="border-b border-gray-900/10 pb-12">
                                        <div>
                                            <h2 class="text-base/7 font-semibold text-gray-900">Cadastro de Documentação</h2>
                                            <div class="flex justify-between">
                                                <p class="mt-1 text-sm/6 text-gray-600">Inclua os documentos da propriedade.</p>
                                                <button type="button" @click="showModal = true" class="rounded-md bg-gray-700 px-3 py-2 my-4 text-sm font-semibold text-white shadow-xs hover:bg-gray-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                                    Adicionar Documento
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between">
                                            <div class="pb-4">
                                                <h2 v-if="form.type_property" class="text-white border border-red-800 p-2 rounded bg-red-600"><b>Documentos Obrigatórios para {{form.type_property === 2 ? 'Propriedades Rurais' : 'Imóveis Urbanos'  }}</b></h2>
                                                <div v-if="form.type_property" class="mt-2 bg-red-600 border border-red-800 rounded p-2">
                                                    <p v-if="form.type_property === 2" class="mt-1 text-sm/6 text-white">
                                                        <b>* Título de propriedade (matrícula/transcrição/outro)</b> 
                                                    </p>
                                                    <p v-if="form.type_property === 2" class="mt-1 text-sm/6 text-white">
                                                        <b>* ⁠CCIR</b>
                                                    </p>
                                                    <p v-if="form.type_property === 2" class="mt-1 text-sm/6 text-white">
                                                        <b>* ⁠ITR</b>
                                                    </p>
                                                    <p v-if="form.type_property === 2" class="mt-1 text-sm/6 text-white">
                                                        <b>* ⁠CAR</b>
                                                    </p>
                                                    <p v-if="form.type_property === 2" class="mt-1 text-sm/6 text-white">
                                                        <b>* ⁠Georreferenciamento (a partir de novembro de 2025)</b>
                                                    </p>
                                                    <p v-if="form.type_property === 1" class="mt-1 text-sm/6 text-white">
                                                        <b>* Título de propriedade</b>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        </div>

                                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                                <thead class="text-xs text-gray-300 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                                    <tr>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
                                                            Nome do Documento
                                                        </th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
                                                            Data de Vencimento
                                                        </th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
                                                            Visualizar
                                                        </th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
                                                            Arquivo
                                                        </th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-400 uppercase tracking-wider">
                                                            Ações
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    <tr v-for="(document, index) in documents" :key="index" class="bg-white border-b dark:bg-gray-600 dark:border-gray-700">
                                                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ document.name }}</td>
                                                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ document.date === null ? "Sem Data" : new Date(document.date).toLocaleDateString('pt-BR') }}</td>
                                                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ document.show === true ? 'Sim' : 'Não' }}</td>
                                                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ document.file_name }}</td>
                                                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                                                            <button type="button" @click="removeDocument(index, document.id)" class="bg-red-500 border border-gray-600 rounded p-1 text-sm/6 font-semibold text-gray-50 hover:bg-red-700">Excluir</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Modal Proprietário-->

                                        <transition name="modalOwner">
                                            <div v-if="showModalOwner" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Adicionar Documento</h3>
                                                    <div>
                                                        <div class="mb-4">
                                                            <label class="block text-sm font-medium text-gray-700">Buscar CPF/CNPJ</label>
                                                            <div class="flex">
                                                                <input type="text" v-model="searchTerm" @input="searchOwners" placeholder="Digite CPF ou CNPJ (somente números)"
                                                                    class="w-full px-3 py-2.5 text-gray-700 rounded-md border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                                            </div>
                                                            <ul v-if="filteredUsers.length > 0" class="mt-2 bg-gray-700 p-2 rounded-md max-h-32 overflow-y-auto">
                                                                <li v-for="user in filteredUsers" :key="user.id" @click="selectOwner(user)" class="cursor-pointer py-1 px-2 hover:bg-indigo-500 rounded-md">
                                                                    {{ user.name }} - {{ applyCpfCnpjMask(user.cpf_cnpj) }}
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label for="percent"
                                                                class="block text-sm font-medium text-gray-700">Percentual</label>
                                                            <div class="flex items-center">
                                                                <div class="relative w-full">
                                                                    <input type="number" id="percent" v-model="selectedOwner.percent" max="100" min="0"
                                                                        class="bg-gray-50 border border-e-0 border-gray-300 text-gray-900 text-sm rounded-s-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-white dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-700 dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                                </div>
                                                                <button id="copy-number" data-copy-to-clipboard-target="phone-numbers" data-tooltip-target="tooltip-phone"
                                                                    class="flex-shrink-0 z-10 inline-flex items-center py-1 px-4 text-sm font-medium text-center text-gray-500 dark:text-gray-400 hover:text-gray-900 bg-gray-100 border border-gray-300 rounded-e-lg hover:bg-gray-200 focus:ring-4 focus:outline-none dark:bg-white dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-700 dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                                    type="button" readonly="readonly">

                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 14.25 6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                                    </svg>

                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label for="type_ownership"
                                                                class="block text-sm font-medium text-gray-900">Relação com o Propriedade</label>
                                                            <div class="mt-2">
                                                                <select id="type_ownership" name="type_ownership" 
                                                                    v-model="selectedOwner.type_ownership"
                                                                    class="block w-full rounded-md bg-white px-3 py-2.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm">
                                                                    <option v-for="type in props.typeOwners" :key="type.id" :value="type.id">{{type.name}}</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label for="observations"
                                                                class="block text-sm font-medium text-gray-700">Observações</label>
                                                            <textarea name="obs" id="obs" rows="3" v-model="selectedOwner.observations"
                                                                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 placeholder:text-gray-400 sm:text-sm">
                                                    </textarea>
                                                        </div>
                                                        <div class="flex justify-end">
                                                            <button @click="outModalOwner" class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400">
                                                                Sair
                                                            </button>
                                                            <button @click="clearOwner" class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400">
                                                                Limpar
                                                            </button>
                                                            <button @click="addOwner" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                                                                Adicionar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>

                                        <!-- Modal Documentos -->

                                        <transition name="modal">
                                            <div v-if="showModal"
                                                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Adicionar Documento</h3>
                                                    <form @submit.prevent>
                                                        <div class="mb-4">
                                                            <label for="document-name"
                                                                class="block text-sm font-medium text-gray-700">Nome do Documento</label>
                                                            <input type="text" id="document-name" v-model="newDocument.name"
                                                                class="mt-1 block w-full text-gray-700 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                                        </div>
                                                        <div class="mb-4">
                                                            <legend class="text-sm/6 font-semibold text-gray-900">Documento possui validade?</legend>
                                                            <div class="mt-3 space-y-3">
                                                                <div class="flex items-center gap-x-1">
                                                                    <input id="push-showDoc"
                                                                        name="push-show" type="radio" v-model="docDate" :value="true"
                                                                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden" />
                                                                    <label for="push-showDoc" class="block text-sm/6 font-medium text-gray-900">Sim</label>
                                                                </div>
                                                                <div class="flex items-center gap-x-1">
                                                                    <input id="push-show" name="push-show" type="radio" v-model="docDate" :value="false"
                                                                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden" />
                                                                    <label for="push-show" class="block text-sm/6 font-medium text-gray-900">Não</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div v-if="docDate === true" class="mb-4">
                                                            <label for="document-date"
                                                                class="block text-sm font-medium text-gray-700">Data de Vencimento</label>
                                                            <input type="date" id="document-date" v-model="newDocument.date"
                                                                class="mt-1 block w-full text-gray-700 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        </div>
                                                        <div class="mb-4">
                                                            <legend class="text-sm/6 font-semibold text-gray-900"> Disponibilizar Documento</legend>
                                                            <p class="mt-1 text-sm/6 text-gray-600">Marque <b>SIM</b> para os documentos que deseja disponibilizar para prestadores de serviços.</p>
                                                            <div class="mt-6 space-y-6">
                                                                <div class="flex items-center gap-x-3">
                                                                    <input id="push-everything"
                                                                        name="push-notifications" type="radio" v-model="newDocument.show" :value="true"
                                                                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden" />
                                                                    <label for="push-everything" class="block text-sm/6 font-medium text-gray-900">Sim</label>
                                                                </div>
                                                                <div class="flex items-center gap-x-3">
                                                                    <input id="push-email" name="push-notifications" type="radio" v-model="newDocument.show" :value="false"
                                                                        class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden" />
                                                                    <label for="push-email" class="block text-sm/6 font-medium text-gray-900">Não</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label for="document-file" class="block text-sm font-medium text-gray-700">Arquivo</label>
                                                            <input type="file" :key="inputKey" id="document-file" @change="handleDocumentUpload" accept=".pdf,.doc,.docx, .kml, .kmz"
                                                                class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-indigo-500" required>
                                                        </div>
                                                        <div class="flex justify-end">
                                                            <button type="button" @click="showModal = false"
                                                                class="mr-2 rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-400 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-gray-500">Cancelar</button>
                                                            <button @click="addDocument"
                                                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Adicionar</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center justify-end gap-x-6">
                                    <button type="button" class="text-sm/6 font-semibold text-gray-900">Cancelar</button>
                                    <button @click="updateForm" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Salvar</button>
                                </div>
                            </form>
                        </div>
                        <div v-if="alert.show === true" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                            <div :class="`rounded-md px-14 py-8 ${alertClass}`">
                                <div class="flex">
                                    <div class="shrink-0">
                                        <ExclamationTriangleIcon v-if="alert.type === 'warning'" class="size-5 text-yellow-400" aria-hidden="true" />
                                        <XCircleIcon v-if="alert.type === 'error'" class="size-5 text-red-400" aria-hidden="true" />
                                        <CheckCircleIcon v-if="alert.type === 'success'" class="size-5 text-green-400" aria-hidden="true" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">{{ alert.message }}</p>
                                    </div>
                                    <div class="ml-auto pl-3">
                                        <div class="-mx-1.5 -my-1.5">
                                            <button type="button" @click="alert.show = false"
                                                class="inline-flex rounded-md bg-gray-50 p-1.5 ml-8 border text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 focus:ring-offset-gray-50">
                                                <XMarkIcon class="size-4" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
