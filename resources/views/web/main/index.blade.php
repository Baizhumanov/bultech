<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <title>Зарплатный калькулятор</title>
</head>
<body>
    <div class="container">
        <div class="row my-5">
            <div class="col">
                <div class="card">
                    <div class="card-header">Зарплатный калькулятор</div>
                    <div class="card-body">
                        <form id="app">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Оклад в тенге</label>
                                <input type="text" class="form-control" id="salary" v-model="formData.salary" placeholder='К примеру, 600000'>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="daysCount" class="form-label">Норма дней в месяце</label>
                                    <input type="text" class="form-control" id="daysCount" v-model="formData.daysCount" placeholder="Обычно 22">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="workDays" class="form-label">Отработанное количество дней</label>
                                    <input type="text" class="form-control" id="workDays" v-model="formData.workDays" placeholder="К примеру, 21">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="calendarYear" class="form-label">Календарьный год</label>
                                    <input type="text" class="form-control" id="calendarYear" v-model="formData.calendarYear" placeholder="2022">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="calendarMonth" class="form-label">Календарьный месяц</label>
                                    <input type="text" class="form-control" id="calendarMonth" v-model="formData.calendarMonth" placeholder="5">
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="hasTax" v-model="formData.hasTax">
                                        <label class="form-check-label" for="hasTax">Имеется налоговый вычет 1 МЗП</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="isPensioner" v-model="formData.isPensioner">
                                        <label class="form-check-label" for="isPensioner">Сотрудник является пенсионером</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-group mb-3">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="isInvalid" v-model="formData.isInvalid">
                                                <label class="form-check-label" for="isInvalid">Сотрудник является инвалидом. Группа: </label>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" v-model="formData.invalidDegree">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" @click="calculate">Посчитать</button>
                                    <button type="button" class="btn btn-success" @click="store">Посчитать и сохранить</button>
                                </div>

                                @verbatim
                                    <div v-if="isSent">
                                        <div v-if="responce.showAlert">
                                            <div v-if="responce.alertType == 'success'">
                                                <div class="alert alert-success">{{ result.text }}</div>
                                            </div>
                                            <div v-else class="alert alert-danger">{{ result }}</div>
                                        </div>

                                        <div v-if="responce.showInfo">
                                            <div class="row mb-3" v-for="(item, index) in result.taxes" :key="index">
                                                <label class="col-sm-4 col-form-label">{{ item.name }}</label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" :value="item.value" disabled readonly>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-sm-4 col-form-label">Начисленная зарплата</label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" :value="result.salary" disabled readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endverbatim

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                message: 'Привет, Vue!',
                formData: {
                    salary: '600000',
                    daysCount: 22,
                    workDays: 22,
                    calendarYear: 2022,
                    calendarMonth: 5,
                    hasTax: true,
                    isPensioner: false,
                    isInvalid: false,
                    invalidDegree: '',
                },
                isSent: false,
                responce: {
                    alertType: 'success',
                    showInfo: false,
                    showAlert: false,
                },
                result: {
                    taxes: {},
                },
            },
            methods: {
                calculate: function () {
                    axios
                        .get('/calculate', {
                            params: {
                                formData: this.formData
                            }
                        })
                        .then(response => {
                            this.result = response.data
                            this.responce.showInfo = true
                            this.responce.showAlert = false
                        })
                        .catch(error => {
                            this.result = error.response.data
                            this.responce.showInfo = false
                            this.responce.showAlert = true
                            this.responce.alertType = 'error'
                        })
                    this.isSent = true
                },
                store: function () {
                    axios
                        .post('/calculate', {
                            formData: this.formData
                        })
                        .then(response => {
                            this.result = response.data
                            this.responce.showInfo = true
                            this.responce.showAlert = true
                            this.responce.alertType = 'success'
                        })
                        .catch(error => {
                            this.result = error.response.data
                            this.responce.showInfo = false
                            this.responce.showAlert = true
                            this.responce.alertType = 'error'
                        })
                    this.isSent = true
                },
            },
        })
    </script>
</body>
</html>
