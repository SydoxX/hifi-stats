package main

import (
	"encoding/json"
	"fmt"
	"time"
	"github.com/robfig/cron"
	"io/ioutil"
	"net/http"
	"errors"
	"database/sql"
	_"github.com/go-sql-driver/mysql"
)

const urlStatsDomains 	= "https://metaverse.highfidelity.com/api/v1/stats/domains"
const urlUsers		= "https://metaverse.highfidelity.com/api/v1/users?status=online"
var insStatsDomains 	*sql.Stmt
var insStatsUsers	*sql.Stmt
var db			*sql.DB
// Container for the JSON which will be returned by the HiFi API
// Works for both domains and users
type Container struct{
	Status string `json:"status"`
	Data struct{
		NumOnline int `json:"num_online"`
		Users []struct {
			Username string `json:"username"`
		}
	}
}
func errorHandling(err error){
	fmt.Println(err)
}
// Registers DB and does error handling
func openDB(){
	var err error
	db, err = sql.Open("mysql", "hifi:Music@/hifi-stats")
    	if err != nil {
        	errorHandling(err)  // Just for example purpose. You should use proper error handling instead of panic
    	}
	err = db.Ping()
	if err != nil {
		errorHandling(err)
	}
}
func prepareInsertDB(){
	var err error
	insStatsDomains, err = db.Prepare("INSERT INTO stats_domains (time, domainCount) VALUES ( ?, ? )")
	if err != nil {
		errorHandling(err)
	}
	insStatsUsers, err = db.Prepare("INSERT INTO stats_users (time, userCount) VALUES ( ?, ? )")
	if err != nil{
		errorHandling(err)
	}
}
// getHttpJson gets the body of the supplied link and returns it as byte.
// Also handles any errors and recovers from any errors, so that the program doesn't exit
func getHttpJson(link string) ([]byte){
	defer func() {
		if x := recover(); x != nil {
			fmt.Println("run time panic: %v", x)
		}
	}()
	res, err := http.Get(link)
	if err != nil {
		errorHandling(err)
	}
	data, err := ioutil.ReadAll(res.Body)
	res.Body.Close()
	if err != nil {
		errorHandling(err)
	}
	return data
}
// This function unmarshals the JSON and returns any errors
func  unmarshalJson(data []byte, container Container) (Container){
	err := json.Unmarshal(data, &container)
	if err != nil {
		errorHandling(err)
	}
	if container.Status != "success" {
		err = errors.New( "Source reached, but status != 'success'!")
		errorHandling(err)
	}
	return container
}
// This function parses the return of 'users' and prints out the time of capture + the user count
// When something went wrong in the whole process, container.Status != 'success', then the userCount will be returned -1
func parseUsers(){
	data := getHttpJson(urlUsers)
	var container Container 
	var userCount int
	container = unmarshalJson(data, container)
	if container.Status != "success" {
		userCount = -1
	} else {
		userCount = len(container.Data.Users)
	}
	t := time.Now()
	fmt.Printf("\n" + t.UTC().Format(time.RFC3339) + "\nUsers Online: " + "%d", userCount)
	fmt.Printf("\n")
	_, err := insStatsUsers.Exec(t.UTC(), userCount)
	if err != nil {
		errorHandling(err)
	}
}
// This function parses the return of 'domains' and prints out the time of capture + the domain count
// When something went wrong in the whole process, container.Status != 'success', then the domainCount will be returned -1
func parseDomains() {
	var container Container
	var domainCount int
	data := getHttpJson(urlStatsDomains)
	container = unmarshalJson(data, container)
	if container.Status != "success" {
		domainCount = -1
	} else {
		domainCount = container.Data.NumOnline
	}
	t := time.Now()
	fmt.Printf("\n" + t.UTC().Format(time.RFC3339) + "\nDomains Online: " + "%+v", domainCount)
	fmt.Printf("\n")
	_, err := insStatsDomains.Exec(t.UTC(), domainCount)
        if err != nil {
            errorHandling(err)
        }
}
func main() {
	openDB()
	// This has to be inside main(), else it'd close the db when it exits the func
	defer db.Close()
	prepareInsertDB()
	// Same as above
	defer insStatsDomains.Close()
	defer insStatsUsers.Close()
	//parseDomains()
	//parseUsers()
	c := cron.New()
	c.AddFunc("0 0 * * * *", parseDomains)
	c.AddFunc("0 * * * * *", parseUsers)	
	c.Start()
	select {}	
}
