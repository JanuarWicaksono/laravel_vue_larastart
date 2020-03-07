<template>
    <div class="row">

        <div v-if="!$gate.isAdminOrAuthor()">
            <not-found></not-found>
        </div>

        <div class="col-md-12" v-if="$gate.isAdminOrAuthor()">
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Users Table</h3>

                    <div class="card-tools">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addNew"
                        @click="newModal">Add New
                            <i class="fas fa-user-plus fa-fw"></i>
                        </button>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Register At</th>
                                <th>Modify</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id">
                                <td>{{ user.id }}</td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>{{ user.type | upText}}</td>
                                <td>{{ user.created_at | myDate}}</td>
                                <td>
                                    <a href="#"><i class="fa fa-edit blue" @click="editModal(user)"></i></a>
                                    |
                                    <a href="#"><i class="fa fa-trash red" @click="deleteUser(user.id)"></i></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <pagination :data="users" @pagination-change-page="getResults"></pagination>
                </div>
            </div>
            <!-- /.card -->
        </div>


        <!-- Modal -->
        <div class="modal fade" id="addNew" tabindex="-1" role="dialog" aria-labelledby="addNewLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" v-show="!editmode" id="addNewLabel">Add New User</h5>
                        <h5 class="modal-title" v-show="editmode" id="addNewLabel">Update User's Info</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="editmode ? updateUser() : createUser()">
                        <div class="modal-body">
                            <div class="form-group">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input v-model="form.name" type="text" name="name" placeholder="name"
                                        class="form-control" :class="{ 'is-invalid': form.errors.has('name') }">
                                    <has-error :form="form" field="name"></has-error>
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input v-model="form.email" type="text" name="email" placeholder="email"
                                        class="form-control" :class="{ 'is-invalid': form.errors.has('email') }">
                                    <has-error :form="form" field="email"></has-error>
                                </div>

                                <div class="form-group">
                                    <label>Bio</label>
                                    <input v-model="form.bio" type="text" name="bio" placeholder="bio"
                                        class="form-control" :class="{ 'is-invalid': form.errors.has('bio') }">
                                    <has-error :form="form" field="bio"></has-error>
                                </div>

                                <div class="form-group">
                                    <label>Type</label>
                                    <select name="type" v-model="form.type" id="type" class="form-control" :class="{'is-valid': form.errors.has('type')}">
                                        <option value="">Select User Role</option>
                                        <option value="admin">Admin</option>
                                        <option value="user">Starndard User</option>
                                        <option value="author">Author</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Password</label>
                                    <input v-model="form.password" type="text" name="password" placeholder="password"
                                        class="form-control" :class="{ 'is-invalid': form.errors.has('password') }">
                                    <has-error :form="form" field="password"></has-error>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button v-show="!editmode" type="submit" class="btn btn-primary">Create</button>
                            <button v-show="editmode" type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


    </div>
</template>

<script>
    export default {
        data() {
            return {
                users: {},
                form: new Form({
                    id: '',
                    name: '',
                    email: '',
                    password: '',
                    type: '',
                    bio: '',
                    photo: ''
                }),
                editmode: true
            }
        },

        methods: {
            // Our method to GET results from a Laravel endpoint
            getResults(page = 1) {
                axios.get('api/user?page=' + page).then(response => {
                    this.users = response.data;
                });
            },
        
            loadUsers(){
                if (this.$gate.isAdminOrAuthor()) {
                    axios.get("api/user").then(({ data }) => (this.users = data));
                }
            },

            createUser(){
                this.$Progress.start();
                this.form.post('api/user').then(() => {
                    
                    Fire.$emit('AfterCreate');
                    $('#addNew').modal('hide');

                    toast.fire({
                        icon: 'success',
                        title: 'Signed in successfully'
                    })

                    this.$Progress.finish();

                }).catch(() => {
                    swal("Failed!", "There was something wronge.", "warning")
                });
                
            },

            editModal(user){
                this.editmode = true;
                this.form.reset();
                $('#addNew').modal('show');
                this.form.fill(user);
            },

            newModal(){
                this.editmode = false;
                this.form.reset();
                $('#addNew').modal('show');
            },

            updateUser(id) {
                this.$Progress.start();
                this.form.put('api/user/'+this.form.id).then(() => {
                    $('#addNew').modal('hide');
                    swal.fire('Updated!', 'Information has been updated.', 'success');
                    this.$Progress.finish();
                    Fire.$emit('AfterCreate');
                }).catch(() => {
                    this.$Progress.fail();
                })
            },

            deleteUser(id){
                swal.fire({
                    title: 'Are you sure ?',
                    text: 'Are you sure ?',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it'
                }).then((result) => {
                    if (result.value) {   
                        this.form.delete('api/user/'+id).then(() => {
                            swal.fire(
                                'Deleted!',
                                'Your file has been delete.',
                                'success'
                            );
                            Fire.$emit('AfterCreate');
                        })
                    }
                })
            }
        },

        mounted() {
            console.log('Component mounted.')
        },

        created() {
            Fire.$on('searching', () => {
                let query = this.$parent.search;
                console.log(query)
                axios.get('api/findUser?q='+ query).then((data) => {
                    this.users = data.data;
                }).catch(() => {
                    
                });
            })
            this.loadUsers();
            Fire.$on('AfterCreate', () => {
                this.loadUsers();
            });
        }
    }

</script>
